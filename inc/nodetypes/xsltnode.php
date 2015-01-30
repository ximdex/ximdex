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

if (!defined('XIMDEX_ROOT_PATH')) {
	define ('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . '/../../'));
}

require_once(XIMDEX_ROOT_PATH . "/inc/nodetypes/filenode.php");
require_once(XIMDEX_ROOT_PATH . '/inc/fsutils/FsUtils.class.php');

class xsltnode extends FileNode {

	private $xsltOldName=""; //String;

	function xsltnode(&$node){

		if (is_object($node))
		    $this->parent = $node;
		else if (is_numeric ($node) || $node == null)
		    $this->parent = new Node($node, false);
		$this->nodeID = $this->parent->get('IdNode');
		$this->dbObj = new DB();
		$this->nodeType = &$this->parent->nodeType;
		$this->messages = new \Ximdex\Utils\Messages();
		$this->xsltOldName = $this->parent->get("Name");		
	}


	function CreateNode($xsltName = null, $parentID = null, $nodeTypeID = null, $stateID = null, $ptdSourcePath = NULL) {
		
		$xslSourcePath=NULL;
	//	if (is_null($ptdSourcePath))  return;
		if ($ptdSourcePath != null){

			// Saving xslt content

			$xslContent = FsUtils::file_get_contents($ptdSourcePath);
			$xslContent = $this->sanitizeContent($xslContent);
	
			$xslSourcePath = \App::getValue( 'AppRoot') . \App::getValue( 'TempRoot') . '/' . $parentID . $xsltName;
	
			if (!FsUtils::file_put_contents($xslSourcePath, $xslContent)) {
				XMD_Log::error("Error saving xslt file");
			}
		}
		parent::CreateNode($xsltName, $parentID, $nodeTypeID, $stateID, $xslSourcePath);

		// Checks if exists template_include.xsl node
		if ($xsltName != 'docxap.xsl') {
			$this->setIncludeContent($xsltName, $parentID, $nodeTypeID, $stateID);
		}

		// Checks if exists docxap.xsl node

		$node = new Node($this->nodeID);
		$ximPtdNode = new Node($parentID);

		$project = new Node($node->GetProject());
		$idXimptdProject = $project->GetChildByName('templates');

		$ptdProject = new Node($idXimptdProject);
		$idDocxapProject = $ptdProject->GetChildByName('docxap.xsl');

		if ( $xsltName != 'docxap.xsl'	&& $ximPtdNode->get('IdParent') != $node->GetProject()
			&& !($ximPtdNode->GetChildByName('docxap.xsl') > 0) && ($idDocxapProject > 0)) {

			// get and copy project docxap

			$docxapProject = new Node($idDocxapProject);
			$docxapContent = $docxapProject->GetContent();

			$docxapProjectPath = XIMDEX_ROOT_PATH . \App::getValue( 'TempRoot') . '/docxap.xsl';

			$dummyXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<dext:root xmlns:dext=\"http://www.ximdex.com\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
				<xsl:dummy />
				</dext:root>";

			FsUtils::file_put_contents($docxapProjectPath, $dummyXml);

			$docxapNode = new Node();
			$id = $docxapNode->CreateNode('docxap.xsl', $parentID, $nodeTypeID, $stateID, $docxapProjectPath);

			if ($id > 0) {
				$docxapNode = new Node($id);
				$docxapNode->SetContent($docxapContent);
			}
		}

	}



	/**
	 *	Make a xsl:include line and call to inserts on inclusion files
	 *
	 */
	function setIncludeContent($fileName, $parentId, $nodeTypeId, $stateID) {

		if ($fileName!="templates_include.xsl"){
			$node = new Node($this->nodeID);
			$projectId = $node->GetProject();

			$ximptd = new Node($parentId);
			$idProject = $node->GetProject();
			$project = new Node($idProject);

			$ptdFolder = \App::getValue( "TemplatesDirName");

			if ($ximptd->get('IdParent') == $projectId) {

				// Making include in project (modify includes from project and its sections)

				$includeString = "<xsl:include href=\"$fileName\"/>\n";
				$this->writeIncludeFile($fileName, $projectId, $nodeTypeId, $stateID, $includeString);

			} else {

				// Making include only in section ximptd
				$sectionId = $node->GetSection();
				$section = new Node($sectionId);
				$includeString = "<xsl:include href=\"$fileName\"/>\n";
				$this->writeIncludeFile($fileName, $sectionId, $nodeTypeId, $stateID, $includeString);
			}

		}else{
			XMD_Log::info("templates_include.xsl wont be include in itself.");
		}
	}

	/**
	 *	Insert xsl:include line in inclusion
	 *
	 *	@param string $includeFile include file
	 *	@param string $includeString line to include
	 *	@param string $templateName template
	 *	@return true / false
	 */

	function writeIncludeFile($templateName, $sectionId, $nodeTypeID, $stateID, $includeString) {

		$section = new Node($sectionId);
		$ximPtdId = $section->GetChildByName('templates');

		$parent = new Node($ximPtdId);
		$includeId = $parent->GetChildByName('templates_include.xsl');

		if (!($includeId > 0)) {

			$xslSourcePath = \App::getValue( 'AppRoot') . \App::getValue( 'TempRoot') . '/templates_include.xsl';

			// Creating include file

			XMD_Log::info("Creating unexisting include xslt file at folder $ximPtdId");

			$includeContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<xsl:stylesheet version=\"1.0\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
			$includeString
			</xsl:stylesheet>";

			$arrayContent = explode("\n", $includeContent);
			$includeContent = implode("\n", array_unique($arrayContent));

			$dummyXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
				<dext:root xmlns:dext=\"http://www.ximdex.com\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
				<xsl:dummy />
				</dext:root>";

			if (!FsUtils::file_put_contents($xslSourcePath, $dummyXml)) {
				XMD_Log::error("Error saving xslt file");
				return false;
			}

			$incNode = new Node();
			$id = $incNode->CreateNode('templates_include.xsl', $ximPtdId, $nodeTypeID, $stateID, $xslSourcePath);

			if ($id > 0) {
				$incNode = new Node($id);
				$incNode->SetContent($includeContent);
			}

		} else {

			$includeNode = new Node($includeId);
			$includeContent = $includeNode->getContent();

			if (preg_match("/include\shref=\"$templateName\"/i", $includeContent, $matches) == 0) {

				XMD_Log::info("Adding include at end");

				$pattern = "/<\/xsl:stylesheet>/i";
				$replacement = $includeString . "\n</xsl:stylesheet>";
				$includeContent = preg_replace($pattern, $replacement, $includeContent);
			}


			$arrayContent = explode("\n", $includeContent);
			$includeContent = implode("\n", array_unique($arrayContent));

			$includeNode->setContent($includeContent);
		}
	}

	function RenameNode($newName = NULL){
		if(null == $newName) return false;

		$nodeTypeId = $this->parent->get("IdNodeType");
		$projectId = $this->parent->GetProject();
		$parentId = $this->parent->get("IdParent");
		$sectionId = $this->parent->getSection();
		$oldName=explode(".",$this->xsltOldName);
		$newName=explode(".",$newName);
		if ($this->xsltOldName){
			$templateName = $this->xsltOldName;
			$this->removeIncludeFile($templateName, $sectionId, $nodeTypeId);
			$this->removeIncludeFile($templateName, $projectId, $nodeTypeId);
		}
		//open the file and make the replacement inside
		$tpl= new Node($this->nodeID);
		$rpl1='name="'.$oldName[0];
		$rpl2='name="'.$newName[0];
		$new_content=str_replace($rpl1,$rpl2,$tpl->GetContent());
		$rpl1='match="'.$oldName[0];
		$rpl2='match="'.$newName[0];
		$new_content=str_replace($rpl1,$rpl2,$new_content);
		$tpl->SetContent($new_content);
	
		$this->setIncludeContent($newName[0].".".$newName[1], $parentId, $nodeTypeId, null);
	}

	function deleteNode() {

		// Deletes dependencies in rel tables

	
		$nodeId = $this->nodeID;
		$node = new Node($nodeId);
		$sectionId = $this->parent->getSection();
		$nodeTypeId = $this->parent->get("IdNodeType");
		$templateName = $this->parent->get("Name");
		$this->removeIncludeFile($templateName, $sectionId, $nodeTypeId);
		
		$projectId = $node->GetProject();
		$this->removeIncludeFile($templateName, $projectId, $nodeTypeId);	
		
		$depsMngr = new DepsManager();
		$depsMngr->deleteByTarget(DepsManager::STRDOC_TEMPLATE, $this->parent->get('IdNode'));

		XMD_Log::info('Xslt dependencies deleted');
	}

	/**
		Remove from template_includes $templateName occurrences
	*/
	private function removeIncludeFile($templateName, $sectionId, $nodeTypeId){
	
	
		$section = new Node($sectionId);	
		$ximPtdId = $section->GetChildByName('templates');

		$parent = new Node($ximPtdId);
		$includeId = $parent->GetChildByName('templates_include.xsl');
		
		
		if ($includeId > 0) {

			$includeNode = new Node($includeId);
			$includeContent = $includeNode->getContent();
			$pattern = "/<xsl:include\shref=\"$templateName\"\/>/i";
			XMD_Log::info("Removing include");
			$replacement = "";
			$includeContent = preg_replace($pattern, $replacement, $includeContent);	
				

			$arrayContent = explode("\n", $includeContent);
			$includeContent = implode("\n", array_unique($arrayContent));

			$includeNode->setContent($includeContent);
		}
	
	}

	function SetContent($content, $commitNode=NULL) {
		$content = $this->sanitizeContent($content);
		parent::SetContent($content, $commitNode);
	}

	private function sanitizeContent($content) {
		if (empty($content)) {
			XMD_Log::info('It have been created or edited a document with empty content');
			return $content;
		}

		$xsldom = new DOMDocument();
		$result = $xsldom->loadXML($content);
		if (!$result) {
			XMD_Log::info('It have been created or edited a document which content is not a valid XML');
			return $content;
		}
		$xpath = new DOMXPath($xsldom);

		$nodelist = $xpath->query('//xsl:text');
		$count = $nodelist->length;
		for ($i=0; $i<$count; $i++) {
			$textnode = $nodelist->item($i);
			// Split CDATA sections if contains attributes references
			$nodes = $this->splitCData($textnode, $xsldom);
			// If splitCData returns only one node there is nothing to change, it's the same node
			if (count($nodes) > 1) {
				$parent = $textnode->parentNode;
				foreach ($nodes as $node) {
					$parent->insertBefore($node, $textnode);
				}
				$parent->removeChild($textnode);
			}
		}

		$content = $xsldom->saveXML();
		return $content;
	}

	private function splitCData($node, &$xsldom) {

		$nodevalue = $node->nodeValue;

		// Split CDATA sections if contains attributes references
		$ret = preg_match_all('/"{@([^}]+)}"/', $nodevalue, $matches);

		if (!$ret) {
			return array($node);
		} else {

			$matches = array_unique($matches[1]);
			$attribute = $matches[0];

			$attrvalue = "@$attribute";
			$sep = '{'.$attrvalue.'}';
			$tokens = explode($sep, $nodevalue);
			$arrCD = array();

			$count = count($tokens);
			for ($i=0; $i<$count; $i++) {

				$token = $tokens[$i];
				$textnode = $xsldom->createElement('xsl:text');
				$textnode->setAttribute('disable-output-escaping', 'yes');
				$textnode->appendChild($xsldom->createCDATASection($token));

				$arrCD = array_merge($arrCD, (array)$this->splitCData($textnode, $xsldom));

				if ($i < ($count-1)) {
					$valueof = $xsldom->createElement('xsl:value-of');
					$valueof->setAttribute('select', $attrvalue);
					$arrCD[] = $valueof;
				}
			}

			return $arrCD;
		}
	}

/**
*	Get the documents that must be publish when the template is published
*	@param array $params
*	@return array
*/
	public function getPublishabledDeps($params) {
			$depsMngr = new DepsManager();
			return $depsMngr->getByTarget(DepsManager::STRDOC_TEMPLATE, $this->parent->get('IdNode'));
	}

}