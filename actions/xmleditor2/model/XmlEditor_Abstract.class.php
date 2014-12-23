<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */


ModulesManager::file('/inc/parsers/pvd2rng/PVD2RNG.class.php');
ModulesManager::file('/inc/model/RelTemplateContainer.class.php');

abstract class XmlEditor_Abstract {

	protected $_editorName = '';
	protected $_base_url = null;

	abstract public function getEditorName();

	abstract public function getBaseURL();

	abstract public function setBaseURL($base_url);

	abstract public function setEditorName($editorName);

	abstract public function openEditor($idnode, $view);

	abstract public function getConfig($idnode);

	abstract public function getSpellCheckingFile($idnode, $content);

	abstract public function getAnnotationFile($idnode, $content);

	abstract public function getPreviewInServerFile ($idNode, $content, $idChannel);

	abstract public function getNoRenderizableElements ($idNode);

	public function getXmlFile($idnode) {
    	$node = new Node($idnode);
    	if (!($node->get('IdNode') > 0)) {
			XMD_Log::error(_("A non-existing node cannot be obtained: ") . $node->get('IdNode'));
			return null;
		}


		// ---------------------------------------------------------------------------------------------
		// TODO: Study the logic about how to obtain an edition channel.
		// For the moment, it is selected the channel HTML; if it is not existing, the first found.
		// ---------------------------------------------------------------------------------------------

		// Channel IDs are returned, but XSLT needs the name
		$channels = $node->getChannels();

		$max = count($channels);
		$defaultChannel = null;
		for($i=0; $i<$max; $i++) {
			$channel = new Channel($channels[$i]);
			$channelName = $channel->getName();
			if ($defaultChannel == null) $defaultChannel =  $channels[$i];
			if (strToUpper($channelName) == 'HTML' || strToUpper($channelName) == 'WEB') {
				$defaultChannel = $channels[$i];
				break;
			}
		}

		// ---------------------------------------------------------------------------------------------

		// Document content is returned with the corresponding docxap labels
    	$content = $node->class->GetRenderizedContent($defaultChannel /*, $content=null, $onlyDocXap=null*/);

		return $content;
	}

	public function getXslFile($idnode, $view, $includesInServer=false) {

		$content = '';
    	$docxap = $this->getXslPath($idnode, false, $view);

		if ($docxap !== null) {
			$content = FsUtils::file_get_contents(str_replace(\App::getValue( 'UrlRoot'), \App::getValue( 'AppRoot'),  $docxap));
			$content = str_replace('./templates_include.xsl', \App::getValue( 'UrlRoot') . '/actions/xmleditor2/views/rngeditor/templates/templates_include.xsl', $content);
			if ($includesInServer){
			    $this->replaceIncludes($content);
			}
			
		} else {
			$msg = "docxap.xsl was not found for node $idnode";
			XMD_Log::error(_($msg));
//			$content = array('error' => array($msg));
		}

		return $content;
	}

	public function getXslPath($idnode, $asURL = false, $view) {

    	$node = new Node($idnode);
    	if (!($node->get('IdNode') > 0)) {
			XMD_Log::error(_("A non-existing node cannot be obtained: " ) . "$idnode");
			return null;
		}
                
                if ($view == "form"){
                    return $this->getFormViewXsl($idnode);
                } else if($view == 'tree') {
                    return \App::getValue( 'UrlRoot') . '/actions/xmleditor2/views/editor/tree/templates/docxap.xsl';
		}

		$nodeTypeName = $node->nodeType->GetName();
		if ($nodeTypeName == 'RngVisualTemplate') {
			return \App::getValue( 'UrlRoot') . '/actions/xmleditor2/views/rngeditor/templates/docxap.xsl';
		}

		$docxap = null;

		// Searching for a docxap document in all the project sections
		while (null !== ($idparent = $node->GetParent()) && $docxap === null) {

			unset($node);
			$node = new Node($idparent);
			$ptdFolder = $node->GetChildByName('templates');

			if ($ptdFolder !== false) {
				$ptdFolder = new Node($ptdFolder);
				$docxap = $ptdFolder->class->getNodePath() . '/docxap.xsl';
				unset($ptdFolder);
				if (!is_readable($docxap)) $docxap = null;
			}
		}

		if ($docxap && $asURL) {
			$docxap = str_replace(\App::getValue( 'AppRoot'), \App::getValue( 'UrlRoot'),  $docxap);
		}

		return $docxap;
	}

	public function getSchemaNode($idnode) {

		$node = new Node($idnode);

		$nodeTypeName = $node->nodeType->GetName();
		if ($nodeTypeName == 'RngVisualTemplate') {
			$rngPath = \App::getValue( 'AppRoot') . '/actions/xmleditor2/views/rngeditor/schema/rng-schema.xml';
			return trim(FsUtils::file_get_contents($rngPath));
		}

		$idcontainer = $node->getParent();
		$reltemplate = new RelTemplateContainer();
		$idTemplate = $reltemplate->getTemplate($idcontainer);

		$templateNode = new Node($idTemplate);
		return $templateNode;
	}

	protected function getSchemaData($idnode) {
		$schemaData = array('id' => null, 'content' => '');
		$node = new Node($idnode);

		if(!is_object($templateNode = $this->getSchemaNode($idnode))) {
			return array('id' => 0, 'content' => $templateNode);
		}
		$schemaId = $templateNode->getID();
		if (!empty($schemaId) &&  $templateNode->nodeType->get('Name') == 'VisualTemplate') {
			$pvdt = new PVD2RNG();
			$pvdt->loadPVD($templateNode->getID(), $node);

			if ($pvdt->transform()) {
				$content = $pvdt->getRNG()->saveXML();
			}
		} else {
			$rngTemplate = new Node($schemaId);
			$content = $rngTemplate->GetContent();
		}

		$schemaData['id'] = $schemaId;
		$schemaData['content'] = $content;
		return $schemaData;
	}

	protected function enrichSchema($schema) {
		return $schema;
	}

	public function getSchemaFile($idnode) {
		$schemaData = $this->getSchemaData($idnode);
		$content = $schemaData['content'];
		//$content = $this->enrichSchema($content);

//		$content = '<root><node>value</node></root>';

		$schema = FsUtils::file_get_contents(\App::getValue( 'AppRoot') . '/actions/xmleditor2/views/common/schema/relaxng-1.0.rng.xml');
		$rngvalidator = new \Ximdex\XML\Validators\RNG();
		$valid = $rngvalidator->validate($schema, $content);

		$errors = $rngvalidator->getErrors();
		if (count($errors) > 0) {

			$content = array('error' => array_merge(array('RNG schema not valid:'), $errors));
		} else {

			$content = preg_replace('/xmlns:xim="([^"]*)"/', sprintf('xmlns:xim="%s"', PVD2RNG::XMLNS_XIM),  $content);
		}

		return $content;
	}

	abstract public function saveXmlFile($idnode, $content, $autoSave = false);

	/**
	 * @param int idnode idNode is needed to get the asociated schema
	 * @param string xmldoc Is the XML string to validate
	 */
	public function validateSchema($idnode, $xmldoc) {

		$xmldoc = '<?xml version="1.0" encoding="UTF-8"?>' . \Ximdex\Utils\String::stripslashes( $xmldoc);
		$schema = $this->getSchemaFile($idnode);

		$rngvalidator = new \Ximdex\XML\Validators\RNG();
		$valid = $rngvalidator->validate($schema, $xmldoc);

		$response = array('valid' => $valid,
						'errors' => $rngvalidator->getErrors()
						);

		return $response;
	}

	public function getAllowedChildrens($idnode, $uid, $htmldoc) {

		$node = new Node($idnode);
		$xmlOrigenContent = $node->class->GetRenderizedContent();

		// Loading XML & HTML content into respective DOM Documents
		$docXmlOrigen = new DOMDocument();
		$docXmlOrigen->loadXML($xmlOrigenContent);
		$docHtml = new DOMDocument();
		$docHtml = $docHtml->loadHTML(\Ximdex\Utils\String::stripslashes( $htmldoc));

		// Transforming HTML into XML
		$htmlTransformer = new HTML2XML();
		$htmlTransformer->loadHTML($docHtml);
		$htmlTransformer->loadXML($docXmlOrigen);
		$htmlTransformer->setXimNode($idnode);

		$xmldoc = null;
		if ($htmlTransformer->transform()) {
			$xmldoc = $htmlTransformer->getXmlContent();
		}

		$node = $htmlTransformer->getNodeWithUID($uid);
		return $node->tagName;
	}

	/**
	 * Delete docxap tags
	 * Delete UID attributes
	 */
	protected function _normalizeXmlDocument($idNode, $xmldoc, $deleteDocxap = true) {

		$xmldoc = '<?xml version="1.0" encoding="UTF-8"?>' . \Ximdex\Utils\String::stripslashes( $xmldoc);
		$doc = new DOMDocument();
		$doc->loadXML($xmldoc);
		$docxap = $doc->firstChild;

		$this->_deleteUIDAttributes($docxap);

		if ($deleteDocxap) {
			$childrens = $docxap->childNodes;
			$l = $childrens->length;

			$xmldoc = '';
			for ($i=0; $i<$l; $i++) {
				$child = $childrens->item($i);
				if ($child->nodeType == 1) {
					$xmldoc .= $doc->saveXML($child);
				}
			}
		} else {
			$xmldoc = $doc->saveXML($docxap);
		}

		return $xmldoc;
	}

	/**
	 * Recursive!
	 * Called by _normalizeXmlDocument()
	 */
	protected function _deleteUIDAttributes(&$node) {
		if ($node->nodeType != 1) return;
		if ($node->hasAttribute('uid')) {
			$node->removeAttribute('uid');
		}
		$childrens = $node->childNodes;
		$count = $childrens->length;
		for ($i=0; $i<$count; $i++) {
			$this->_deleteUIDAttributes($childrens->item($i));
		}
	}

	/**
	 * Replace xsl:include tags by the content of file included
	 */
	private function replaceIncludes(&$content){
	    $xsl = new DOMDocument();
	    $xsl->loadXML($content);
	    //Get template-include tag and is source

	    //Get template include path
	    $arrayTags = $xsl->getElementsByTagName("include");
	    //We supposed just 1 include in docxap file (template_includes)
	    if ($arrayTags->item(0) != null){		
		$templateIncludeTag = $arrayTags->item(0);
		//this href is a complete url path
		$templateIncludePath = $templateIncludeTag->getAttribute("href");
		$folderPath = substr($templateIncludePath, 0, strrpos($templateIncludePath, "/")+1);		
		$xslTemplateInclude = new DOMDocument();
		$xslTemplateInclude->load($templateIncludePath);		
		$arrayFinalTags = $xslTemplateInclude->getElementsByTagName("include");		

		$auxContent="";
		//for each include in template_includes
		foreach ($arrayFinalTags as $domElement) {
		    
		    $auxPath = $domElement->getAttribute("href");
		    $auxXsl = new DOMDocument();
		    $auxXsl->load($folderPath.$auxPath);
		    $temporalContent = $auxXsl->saveXML();		    
		    $temporalContent = preg_replace("/\<\/*xsl:stylesheet.*\>/", "", $temporalContent);
		    $temporalContent = preg_replace("/\<\?xml version.*\>/", "", $temporalContent);
		    $auxContent .= $temporalContent;		    
		}
		$content = preg_replace("/\<xsl:include.*href=\".*templates_include.xsl\".*\>/",$auxContent,$content);
	    }
	}
        
        
        private function getFormViewXsl ($idnode){
        
            $node = new Node($idnode);
            $idSchema = $node->class->getTemplate();
            $dataFactory = new DataFactory($idSchema);
            $maxIdVersion = $dataFactory->GetLastVersionId();
            $formXslFile = \App::getValue( 'UrlRoot').\App::getValue( 'FileRoot')."/xslformview_{$maxIdVersion}.xsl";            
            if (file_exists($formXslFile) && false){
                return $formXslFile;
            }else{
                return $this->buildFormXsl($idSchema, $maxIdVersion);
            }
            
        }
        
        private function buildFormXsl ($idSchema, $maxIdVersion){
                   
            
            $schemaNode = new Node($idSchema);
            $schemaContent = $schemaNode->GetContent();
            $docRNG = new DOMDocument();
            $docRNG->validateOnParse=true;
            
            
	    //Removing namespaces declaration.
            $schemaContent = preg_replace('/<grammar[^>]*>/', "<grammar>", $schemaContent,1);            
            $content = FsUtils::file_get_contents(\App::getValue( 'AppRoot') . '/actions/xmleditor2/views/editor/form/templates/docxap.xsl');
            $docRNG->loadXML($schemaContent,LIBXML_NOERROR);


            $xpathObj = new DOMXPath($docRNG);
            $xpathObj->registerNameSpace('xim', 'http://www.ximdex.com');
            
            $textElements = $this->getTextElements($xpathObj);
            $elements = $xpathObj->query("//element");
            $inputTextElements = $textAreaElements = $containerElements = array();
            $applyElements = $this->getApplyElements($xpathObj);

            $boldElements = $this->getBoldElements($xpathObj);
            $italicElements = $this->getItalicElements($xpathObj);
            $linkElements = $this->getLinkElements($xpathObj);

            $imageElements = $this->getElementsByType($xpathObj,"image");
            $listElements = $this->getElementsByType($xpathObj,"list");
            $itemElements = $this->getElementsByType($xpathObj,"item");
            $textAreaElements = $this->getElementsByType($xpathObj,"textarea"); //Se puede forzar a que sea textarea.
	    foreach ($elements as $element) {
    		$tagName = $element->getAttribute("name");
            if ($tagName != "docxap"){
			    $toLowerTagName = strtolower($tagName);
			    //Tomar apply elements, son tipo bold, cursiva, link, enlace
		    	if (in_array($toLowerTagName, $applyElements)){
					continue;

	    		}else if(in_array($toLowerTagName, $textAreaElements)) {
	    			continue;
	    		}else if (in_array($tagName, $textElements)){
			    	$resultLength = $xpathObj->query(".//element", $element)->length + 
			    	$xpathObj->query(".//ref", $element)->length;
			    	if ($resultLength){
					    $textAreaElements[] = $tagName;
					    $textAreaElements[] = $toLowerTagName;
				    }else{
				    	$inputTextElements[] = $tagName;
					    $inputTextElements[] = $toLowerTagName;
				    } 
			    }else{
				    $containerElements[] = $tagName;
				    $containerElements[] = $toLowerTagName;
		    	}                
			}
	    }
	    
	    $applyElements = array_values(array_unique(array_diff($applyElements, $boldElements, $italicElements, $linkElements)));
	    $containerElements = array_values(array_unique(array_diff($containerElements, $imageElements, $listElements)));
	    $textAreaElements = array_values(array_unique(array_diff($textAreaElements, $itemElements)));
	    $inputTextElements = array_values(array_unique(array_diff($inputTextElements, $itemElements)));
	    $blockEditionElements = array_values(array_unique(array_merge($imageElements,$textAreaElements, $listElements)));
	    
	    $stringContainerElements = implode(" | ", $containerElements);
	    $stringTextAreaElements = implode(" | ", $textAreaElements);
	    $stringInputTextElements = implode(" | ", $inputTextElements);
	    $stringApplyElements = implode(" | ", $applyElements);
	    $stringBoldElements = implode(" | ", $boldElements);
	    $stringItalicElements = implode(" | ", $italicElements);
	    $stringLinkElements = implode(" | ", $linkElements);
	    $stringblockEditionElements = implode(" | ", $blockEditionElements);
	    $stringImageElements = implode(" | ", $imageElements);
	    $stringListElements = implode(" | ", $listElements);
	    $stringItemElements = implode(" | ", $itemElements);
	    error_log("DEBUG $stringItemElements");
	    
        $content = str_replace("@@CONTAINER_ELEMENTS@@", $stringContainerElements, $content);
        $content = str_replace("@@INPUT_TEXT_ELEMENTS@@", $stringInputTextElements, $content);
        $content = str_replace("@@TEXTAREA_ELEMENTS@@", $stringTextAreaElements, $content);
	    $content = str_replace("@@APPLY_ELEMENTS@@", $stringApplyElements, $content);
	    $content = str_replace("@@BOLD_ELEMENTS@@", $stringBoldElements, $content);
	    $content = str_replace("@@ITALIC_ELEMENTS@@", $stringItalicElements, $content);
	    $content = str_replace("@@LINK_ELEMENTS@@", $stringLinkElements, $content);
        $content = str_replace("@@URL_PATH@@", Config::getValue("UrlRoot"), $content);
	    $content = str_replace("@@BLOCK_EDITION_ELEMENTS@@", $stringblockEditionElements, $content);
	    $content = str_replace("@@IMAGE_ELEMENTS@@", $stringImageElements, $content);
	    $content = str_replace("@@LIST_ELEMENTS@@", $stringListElements, $content);
	    $content = str_replace("@@ITEM_ELEMENTS@@", $stringItemElements, $content);
	    
            $formViewFile = \App::getValue( 'AppRoot').\App::getValue('FileRoot')."/xslformview_{$maxIdVersion}.xsl";
            FsUtils::file_put_contents($formViewFile, $content);
            return $formViewFile;
            
        }
	
	private function getBoldElements($xpathObj){
	    
	    return $this->getSpecialApplyElements($xpathObj, "bold");
	}
	
	private function getItalicElements($xpathObj){
	    
	    return $this->getSpecialApplyElements($xpathObj, "italic");
	}
	
	private function getLinkElements($xpathObj){
	    
	    return $this->getSpecialApplyElements($xpathObj, "link");
	}
	
	/**
	 */
	private function getSpecialApplyElements($xpathObj,$elementType){
	    
	    $result = array();
	    $applytags = $xpathObj->query("//type[contains(text(),'$elementType')]");
	    foreach ($applytags as $applyTag) {
		    if (strpos($applyTag->nodeValue, "apply")!==FALSE){
			    $elementTag = $applyTag->parentNode;
			    $elementName = $elementTag->getAttribute("name");
			    $result[] = strtolower($elementName);
		    }
	    }	    
	    return $result;
	}
        
        private function getApplyElements(&$xpathObj){

		$result = array();
		$applytags = $xpathObj->query("//*[name()='xim:type']");
		foreach ($applytags as $applyTag) {
			if (strpos($applyTag->nodeValue, "apply")!==FALSE){
				$elementTag = $applyTag->parentNode;
				$elementName = $elementTag->getAttribute("name");
				$result[] = strtolower($elementName);
			}
		}
		return $result;
	}

	private function getElementsByType(&$xpathObj,$elementType){

		$result = array();
		$applytags = $xpathObj->query("//*[name()='xim:type']");
		error_log($elementType." ".count($applytags));
		error_log(print_r($applytags,true));


		foreach ($applytags as $applyTag) {
			error_log($applyTag->nodeValue.", $elementType");
			if (strpos($applyTag->nodeValue, $elementType)!==FALSE){
				$elementTag = $applyTag->parentNode;
				error_log(print_r($elementTag, true));
				$elementName = $elementTag->getAttribute("name");
				$result[] = strtolower($elementName);
			}
		}
		return $result;
	}

	private function getTextElements(&$xpathObj){
		$result = array();
		$elementsTag = $xpathObj->query("//text/ancestor::element");		
		foreach ($elementsTag as $elementTag) {			
				$elementName = $elementTag->getAttribute("name");
				$result[] = $elementName;			
		}
		return $result;
	}
        
}

