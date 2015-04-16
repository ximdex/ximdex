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


ModulesManager::file('/inc/serializer/Serializer.class.php');
ModulesManager::file('/inc/mvc/Request.class.php');
ModulesManager::file('/actions/xmleditor2/XimlinkResolver.class.php');
ModulesManager::file('/actions/createlink/Action_createlink.class.php');
ModulesManager::file('/inc/i18n/I18N.class.php');
ModulesManager::file('/inc/model/locale.php');
ModulesManager::file('/inc/model/NodeEdition.class.php');


class Action_xmleditor2 extends ActionAbstract {

	private $_editor = null;

	public function index() {
		$idnode = $this->request->getParam('nodeid');
		$view = $this->request->getParam('view');

		$strDoc = new StructuredDocument($idnode);
                if($strDoc->GetSymLink()) {
                    $masterNode = new Node($strDoc->GetSymLink());
                    $values = array(
                        'path_master' => $masterNode->GetPath()
                    );
                    $this->render($values, 'linked_document', 'default-3.0.tpl');
                    return false;
                }

		$queryManager = \Ximdex\Runtime\App::get('\Ximdex\Utils\QueryManager');
		$locale = new XimLocale();
		$user_locale = $locale->GetLocaleByCode(\Ximdex\Utils\Session::get('locale'));
		$locales = $locale->GetEnabledLocales();

		$action = $queryManager->getPage() . $queryManager->buildWith(array(
			'method' => 'load',
			'on_resize_functions' => '',
			'time_id' => microtime(true),  //timestamp for javascripts
			'user_locale' => $user_locale,
			'action' => 'xmleditor2',
			'nodeid' => $idnode
		));
		$this->render(array('action' => $action), NULL, 'iframe.tpl');
	}

	// Main method: shows initial form
	public function load() {
		$idnode = $this->request->getParam('nodeid');
		$view = $this->request->getParam('view');
		$this->getEditor($idnode);

		$xslIncludesOnServer = \App::getValue( "XslIncludesOnServer");
		$values = $this->_editor->openEditor($idnode, $view);
		$values['on_resize_functions'] = '';
		$values['xinversion'] = \App::getValue( "VersionName");
		$template = 'loadEditor_' . $this->_editor->getEditorName();
		//Adding Config params for xsl:includes
		$values["xslIncludesOnServer"] = $xslIncludesOnServer;


		$values["user_connect"] = null;
		$values['time_id'] = 0;
		if(ModulesManager::isEnabled('ximADM') ) {
			$userID = (int) \Ximdex\Utils\Session::get('userID');

			$time_id = time()."_".$userID;
			$values['time_id'] = $time_id;
			$values["user_connect"] = $this->addJs('/utils/user_connect.js.php?id='.$time_id.'&lang='.\Ximdex\Utils\Session::get('locale'), 'ximADM');
		}



		$this->render($values, $template, 'xmleditor2.tpl');

	}

	private function &getEditor($idnode) {

		$params = $this->request->getParam("params");

		$editorName = strtoupper('KUPU');
		$msg = new \Ximdex\Utils\Messages();

		$class = 'XmlEditor_' . $editorName;
		$file =  '/actions/xmleditor2/model/XmlEditor_' . $editorName . '.class.php';
		$editor = null;

		if (!is_readable(XIMDEX_ROOT_PATH .$file)) {
			$msg->add(_('A non-existing editor has been refered.'), MSG_TYPE_ERROR);
			$this->render(array('nodeid' => $idnode, 'messages' => $msg->messages));
			exit();
		}

		 ModulesManager::file($file);

		if (!class_exists($class)) {
			$msg->add(_('A non-existing editor has been refered.'), MSG_TYPE_ERROR);
			$this->render(array('nodeid' => $idnode, 'messages' => $msg->messages));
			exit();
		}

		$query = \Ximdex\Runtime\App::get('\Ximdex\Utils\QueryManager');
		$base_url = $query->getPage() . $query->buildWith(array());

		$editor = new $class();
		$editor->setBaseURL($base_url);
		$editor->setEditorName($editorName);
		$this->_editor = & $editor;
		return $editor;
	}

	private function printContent($content, $serialize=true) {
		// TODO: Use MVC renderers?, JSON renderer?, ...


		$ajax = $this->request->getParam('ajax');

		if ($ajax != 'json') {
			// TODO: Detect content type, at the moment is XML...
			header('Content-type: text/xml');
		} else {
			if ($serialize) {
				// TODO: Return the response through the MVC... (I don't like JSON implementation on the MVC !!!)
				if (!is_array($content) && !is_object($content)) {
					$content = array('data' => $content);
				}
				$content = Serializer::encode(SZR_JSON, $content);
			}
			header('Content-type: application/json');
		}

		print $content;
		exit();
	}

	public function getConfig() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->getConfig($idnode);
		$this->printContent($content);
	}

	public function getInfo() {
		$idnode = $this->request->getParam('nodeid');

		$node = new Node($idnode);
		$info = $node->loadData();
		if(!empty($info ) ) {
			$info = json_encode($info);
		}
		echo $info;
		die();
	}

	public function getXmlFile() {
		$idnode = $this->request->getParam('nodeid');
		$view = $this->request->getParam('view');
		$content = $this->request->getParam('content');
		$this->getEditor($idnode);
		$content = $this->_editor->getXmlFile($idnode, $view, $content);
		$this->printContent($content);
	}

	public function verifyTmpFile() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->verifyTmpFile($idnode);
		$this->printContent($content);
	}

	public function removeTmpFile() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->removeTmpFile($idnode);
		$this->printContent($content);
	}

	public function recoverTmpFile() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->recoverTmpFile($idnode);
		$this->printContent($content);
	}

	public function getXslFile() {
		$idnode = $this->request->getParam('nodeid');
		$view = $this->request->getParam('view');
		$includesOnServer = $this->request->getParam("includesInServer");
		$this->getEditor($idnode);
		$content = $this->_editor->getXslFile($idnode, $view, $includesOnServer);
		$this->printContent($content);
	}

	public function getSchemaFile() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->getSchemaFile($idnode);
		$this->printContent($content);
	}

	public function canEditNode() {
		$ximcludeId = $this->request->getParam('nodeid');
		$userId = \Ximdex\Utils\Session::get('userID');
		$ret = Auth::canWrite($userId, array('node_id' => $ximcludeId));
		$this->printContent(array('editable' => $ret));
	}

	public function validateSchema() {
		$idnode = $this->request->getParam('nodeid');
		$xmldoc = Request::post('content');
		$xmldoc = \Ximdex\Utils\String::stripslashes( $xmldoc);
		$this->getEditor($idnode);
		$ret = $this->_editor->validateSchema($idnode, $xmldoc);
		$this->printContent($ret);
	}

	public function saveXmlFile() {
		$idnode = $this->request->getParam('nodeid');
		$content = Request::post('content');
		$autoSave = ($this->request->getParam('autosave') == 'true') ? true : false;
		$this->getEditor($idnode);
		$response = $this->_editor->saveXmlFile($idnode, $content, $autoSave);

		// TODO: Evaluate $response['saved']...


		foreach ($response['headers'] as $header) {
			header($header);
		}
		$this->printContent($response['content']);
	}

	public function publicateFile() {
		$idnode = $this->request->getParam('nodeid');
		$content = Request::post('content');
		$this->getEditor($idnode);
		$response = $this->_editor->publicateFile($idnode, $content);

		foreach ($response['headers'] as $header) {
			header($header);
		}
		$this->printContent($response['content']);
	}

	public function getSpellCheckingFile() {
		$idnode = $this->request->getParam('nodeid');
		$content = Request::post('content');
		$this->getEditor($idnode);
		$content = $this->_editor->getSpellCheckingFile($idnode, $content);
		$this->printContent($content);
	}

	public function getAnnotationFile() {
		$idnode = $this->request->getParam('nodeid');
		$content = Request::post('content');
		$this->getEditor($idnode);
		$content = $this->_editor->getAnnotationFile($idnode, $content);
		$this->printContent($content);
	}

	/**
	 * Returns a JSON string with the allowed nodes under especified uid
	 */
	public function getAllowedChildrens() {
		$idnode = $this->request->getParam('nodeid');
		$uid = $this->request->getParam('uid');
		$content = $this->request->getParam('content');
		$this->getEditor($idnode);
		$allowedChildrens = $this->_editor->getAllowedChildrens($idnode, $uid, $content);
		$this->printContent($allowedChildrens);
	}

	public function getPreviewInServerFile() {
		$idnode = $this->request->getParam('nodeid');
		$content = Request::post('content');
		$idChannel = Request::post('channelid');
		$this->getEditor($idnode);
		$content = $this->_editor->getPreviewInServerFile($idnode, $content, $idChannel);
		$this->printContent($content);
	}

	public function getNoRenderizableElements() {
		$idnode = $this->request->getParam('nodeid');
		$this->getEditor($idnode);
		$content = $this->_editor->getNoRenderizableElements($idnode);
		$this->printContent($content);
	}

	public function getAvailableXimlinks() {

		$docid = $this->request->getParam('docid');
		$term =  $this->request->getParam('term');

		$xr = new XimlinkResolver();
		$data = $xr->getAvailableXimlinks($docid,$term);

		$this->sendJSON($data);
	}

	public function resolveXimlinkUrl() {

		$idnode = $this->request->getParam('nodeid');
		$channel = $this->request->getParam('channel');

		$xr = new XimlinkResolver();
		$data = $xr->resolveXimlinkUrl($idnode, $channel);

		$this->sendJSON($data);
	}

    public function getAll(){
        $idnode = $this->request->getParam('nodeid');
        $view = $this->request->getParam('view');
        $content = $this->request->getParam('content');
        $this->getEditor($idnode);
        // Get XML File
        $contentXML = $this->_editor->getXmlFile($idnode, $view, $content);
        $res = array();
        $res['xmlFile'] = $contentXML;
        // Get Schema File
        $contentRNG = $this->_editor->getSchemaFile($idnode);
        $res['schemaFile'] = $contentRNG;
        // Get XSL File
        $view = $this->request->getParam('view');
        $includesOnServer = $this->request->getParam("includesInServer");
        $this->getEditor($idnode);
        $contentXSL = $this->_editor->getXslFile($idnode, $view, $includesOnServer);
        $res['xslFile'] = $contentXSL;
        // No Renderizable Elements
        $contentNoRender = $this->_editor->getNoRenderizableElements($idnode);
        $res['noRenderizableElements'] = $contentNoRender;
        // Get Config
        $contentConfig = $this->_editor->getConfig($idnode);
        $res['config'] = $contentConfig;
        // Print JSON
        $content = Serializer::encode(SZR_JSON, $res);
        header('Content-type: application/json');
        echo $content;
        exit();
    }

/**
* <p>Check whether the node is being edited by some user</p>
*
* @return string json string containing editing information
*/
public function checkEditionStatus() {
    $idnode = $this->request->getParam('nodeid');
    $userID = (int) \Ximdex\Utils\Session::get('userID');
    $nodeEdition = new NodeEdition();
    $results = $nodeEdition->getByNode($idnode);
    $edition = false;
    $extraEdition = array();
    if(count($results) > 0) {
        $edition = true;
        $userNames = array();
        foreach($results as $result) {
            if(!isset($userNames[$result["IdUser"]])) {
                $user = new User($result["IdUser"]);
                $userNames[$result["IdUser"]] = $user->GetRealName();
            }
            $extra = array('user' => $userNames[$result["IdUser"]],
            'startTime' => $result["StartTime"]);
            array_push($extraEdition, $extra);
        }
    }
    // Creating the new edition for this user
    $res = $nodeEdition->create($idnode, $userID);
    if(!$res) {
        XMD_Log::error(_('Error creating a new Node Edition'));
    }
    $return = array('edition' => $edition,'data' => $extraEdition);
    echo json_encode($return);
}
/**
* <p>Removes a node edition according to a given node and user</p>
*/
public function removeNodeEdition() {
    $nodeid = $this->request->get('nodeid');
    $userid = \Ximdex\Utils\Session::get('userID');
    $nodeEdition = new NodeEdition();
    $res = $nodeEdition->deleteByNodeAndUser($nodeid, $userid);
    if(!$res) {
        XMD_Log::error("Error deleting Node Edition for node ".$nodeid." and user ".$userid);
    }
}


	public function saveXimLink(){
		$result = array();
		$url = urlencode($this->request->getParam("url"));
		$idParent = $this->request->getParam("idParent");
		$name = $this->request->getParam("name");
		$description = $this->request->getParam("description");
		//Check if name is available for the selected parent.
		$nodeParent = new Node($idParent);
		if ($nodeParent->getChildByName($name)){
			$result["success"] = false;
			$result["message"] = _("A link with that name already exists in the selected folder");
		}
		$actionCreateLink = new Action_createlink();
		$idLink = $actionCreateLink->createNodeLink($name, $url, $description, $idParent);
		if ($idLink){
			$result["success"] = true;
			$result["idLink"] = $idLink;
		}

		$this->sendJSON($result);
	}

	public function getLinkFolder(){
		$result = array();
		$idNode = $this->request->getParam("nodeid");
		$node = new Node($idNode);
		$idProject = $node->getProject();
		$project = new Node($idProject);
		$children = $project->getChildren(5050);
		$result["success"] = true;
		$result["idLinkFolder"] = $children[0];
		$this->sendJSON($result);
	}

}

?>