<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 13/02/17
 * Time: 14:02
 */

namespace Ximdex\API\Core;


use Laravel\Lumen\Routing\Controller;
use Ximdex\API\APIResponse;
use Illuminate\Http\Request;
use Ximdex\Models\Channel;
use Ximdex\Models\Language;
use Ximdex\Models\Node;
use Ximdex\Models\NodeType;
use Ximdex\Models\User;
use Ximdex\Runtime\App;
use Ximdex\Services\Node as NodeService;
use Ximdex\Utils\Session;
use Ximdex\Workflow\WorkFlow;

require_once(XIMDEX_ROOT_PATH . '/inc/model/RelNodeTypeMimeType.class.php');
ModulesManager::file('/inc/io/BaseIOInferer.class.php');

ModulesManager::file('/inc/i18n/I18N.class.php');
ModulesManager::file('/inc/utils.php');
ModulesManager::file('/inc/utils/XHTMLEditorUtils.php', 'xBlog');

class NodeController extends Controller  {
    const SEPARATOR = ",";

    /**
     * <p>Default method for this action</p>
     * <p>Get the info of the node passed as parameter</p>
     * @param $request The current request
     * @param $response The response object to be sent and where to put the response of this action
     */
    public function index( Request $request ) {
        $response = new APIResponse;

        $nodeid = $request->input('nodeid', "");

        if (!$this->checkParameters($request, $response)) {
            return;
        }

        $nodeInfo = $this->getNodeInfo($nodeid);
        $childInfo=array();
        foreach($nodeInfo["children"] as $chId){
            $ch = new Node($chId);
            $c = array("nodeid"=>$ch->GetID(),"name"=>$ch->GetNodeName(),"nodetype"=>$ch->GetNodeType());
            if($ch->GetNodeType() == NodeTypeID::IMAGE_FILE){
                $info = $ch->GetLastVersion();
                $file = App::get('UrlRoot') . '/data/files/' . $info['File'];
                $fileLocal = App::get('AppRoot') . '/data/files/' . $info['File'];
                if(!file_exists($fileLocal) || !@is_array(getimagesize($fileLocal))){
                    continue;
                }
                $c['file'] = $file;
                list($width, $height) = getimagesize($fileLocal);
                $c['width'] = $width;
                $c['height'] = $height;
            }

            $childInfo[] = $c;
        }
        $nodeInfo["children"]=$childInfo;

        return $response->setResponse($nodeInfo);
    }

    /**
     * <p>Creates a new empty node</p>
     * <p>It uses the id of the node where to insert the new one</p>
     * @param $request The current request
     * @param $response The response
     * param Response The Response object to be sent
     */
    public function create( Request $request  ) {
        $response = new APIResponse;

        $parentId = $request->input("nodeid", "");
        $nodeType = $request->input("nodetype", "");
        $name = $request->input("name", "");

        if ( empty($parentId) || empty($nodeType) || empty($name) ) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('Some required parameters are missing');
        }

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        //getting and adding file extension
        $rntmt = new RelNodeTypeMimeType();
        $ext = $rntmt->getFileExtension($nodeType);
        $ext = $ext == "image"? "": $ext;
        if (strcmp($ext, '') != 0) {
            $name_ext = $name . "." . $ext;
        } else {
            $name_ext = $name;
        }
        // creating new node
        $node = new Node();
        $idfile = $node->CreateNode($name_ext, $parentId, $nodeType, null);

        if ($idfile > 0) {
            $content = $this->getDefaultContent($nodeType, $name);
            $node->SetContent($content);
            $response->header_status('200');
            $respContent = array('nodeid' => $idfile);
            $response->setResponse($respContent);
        } else {
            $errorMsg = trim($node->messages->getRaw());
            $response->setStatus(APIResponse::ERROR)->setMessage("An error ocurred creating the new empty node. " . $errorMsg);
        }

        return $response;
    }

    /**
     * <p>Sets the content of a node of any type (css, js, text, xml ...)</p>
     * @param type $request
     * @param type $response
     */
    public function content( Request $request  ) {
        $response = new APIResponse;

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $idnode = $request->get('nodeid', false, "");
        $content = $request->get('content', false, "");

        $content = rawurldecode(stripslashes($content));
        $content = str_replace(" ","+",$content);
        $content = base64_decode($content, true);

        if ( empty($content) || empty($content) ) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('Parameter content is missing or invalid');
        }

        $node = new Node($idnode);

        if($node->GetNodeType() == NodeTypeID::XML_DOCUMENT){
            $utils = new HTMLEditorUtils();
            $content = $utils->setContentFromEditor($content);
        }

        $node->SetContent($content);

        return $response->setMessage('Content updated successfully');
    }

    /**
     * <p>Checks whether the required parameters are present in the request
     * and modifies the response accordingly</p>
     *
     * @param $request the request
     * @param $response the response
     * @return true if all required parameters are present and valid and false otherwise
     */
    private function checkParameters(Request $request ) {
        $response = new APIResponse;

        $nodeid = $request->input('nodeid', "");

        /* @var $user User */
        $user = $request->user();
        $username = $user->getLogin();

        $node = new Node($nodeid);

        if ($nodeid == null) {
            $response->setStatus(APIResponse::ERROR)->setMessage('The nodeid parameter is missing');
            return false;
        }
        if ($node->GetID() == null) {
            $response->setStatus(APIResponse::ERROR)->setMessage('The node ' . $nodeid . ' does not exist');
            return false;
        }

        $nodeService = new NodeService();

        $hasPermissionOnNode = $nodeService->hasPermissionOnNode($username, $nodeid, "View all nodes");
        if (!$hasPermissionOnNode) {
            $response->setStatus(APIResponse::ERROR)->setMessage('The user does not have permission on node ' . $nodeid);
            return false;
        }

        return true;
    }

    /**
     * <p>Gets the node info</p>
     * <p>It will return the following properties of the node:
     *  <ul>
     *      <li>nodeid</li>
     *      <li>nodeType</li>
     *      <li>name</li>
     *      <li>version (for nodes having version or 0 otherwise)</li>
     *      <li>creationDate (timestamp)</li>
     *      <li>modificationDate (timestamp)</li>
     *      <li>path</li>
     *      <li>parent</li>
     *      <li>children</li>
     *  </ul>
     * </p>
     *
     * @param string $nodeid the node id to get the information
     * @return array containing the node information
     */
    private function getNodeInfo($nodeid) {
        $node = new Node($nodeid);
        $info = $node->GetLastVersion();
        return array(
            'nodeid' => $node->GetID(),
            'nodeType' => $node->GetNodeType(),
            'name' => $node->GetNodeName(),
            'version' => $node->GetLastVersion() ? $node->GetLastVersion() : 0,
            'creationDate' => $node->get('CreationDate'),
            'modificationDate' => $node->get('ModificationDate'),
            'path' => $node->GetPath(),
            'url' => App::get('UrlRoot') . '/data/files/' . $info['File'],
            'parent' => $node->GetParent(),
            'children' => $node->GetChildren(null, [
                'FIELD' => 'ModificationDate',
                'DIR' => 'DESC',
            ]),
        );
    }

    public function info( Request $request )
    {
        $response = new APIResponse;

        if (!$this->checkParameters($request, $response )) {
            return;
        }
        $nodeid = $request->input('nodeid', "");
        $node = new Node($nodeid);
        $info = $node->loadData();

        return $response->setResponse($info);
    }

    /**
     * <p>Gets the defult content depending on the nodetype</p>
     * @param int $nt the nodetype to get the default content
     * @param string $name the name of the new node
     * @return string the default content for the given nodetype
     */
    private function getDefaultContent($nt, $name) {
        $content = "";
        switch ($nt) {
            case 5039:
                $content = "<<< DELETE \nTHIS\n CONTENT >>>";
                break;

            case 5028:
                $content = "/* CSS File: " . $name . ". Write your rules here. */\n\n * {}";
                break;

            case 5077:
                $content = "<?xml version='1.0' encoding='utf-8'?>\n<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'>\n<xsl:template name='" . $name . "' match='" . $name . "'>\n<!-- Insert your code here -->\n</xsl:template>\n</xsl:stylesheet>";
                break;

            case 5078:
                $content = "<?xml version='1.0' encoding='UTF-8' ?>\n<grammar xmlns='http://relaxng.org/ns/structure/1.0' xmlns:xim='http://ximdex.com/schema/1.0'>\n<!-- Create your own grammar here -->\n<!-- Need help? Visit: http://relaxng.org/tutorial-20011203.html -->\n</grammar>";
                break;

            case 5044:
                $content = "<?xml version='1.0' encoding='UTF-8'?>\n<" . $name . "><!-- Create here your own template -->\n</" . $name . ">";
                break;

            case 5045:
                $content = "<?xml version='1.0' encoding='UTF-8'?>\n<editviews xmlns:edx='msnbc-edx-edit-view'>\n<!-- Create here your views -->\n</editviews>\n##########";
                break;

            case 5076:
                $content = "<html>\n<head>\n</head>\n<body>\n</body>\n</html>";
                break;
        }
        return $content;
    }

    /**
     * <p>Creates a new XML container</p>
     * @param $request the current request
     * @param $response the response
     */
    public function createxml(Request $request ) {
        $response = new APIResponse;

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $idNode = $request->input('nodeid', "");
        $node = new Node($idNode);

        /* Check whether it is possible to add a xml container as child of the supplied node */
        $nodeAllowedContent = new NodeAllowedContent();
        $allowedContents = $nodeAllowedContent->getAllowedChilds($node->GetNodeType());
        $xhtmlContNt = NT::XHTML5_CONTAINER;
        if (!in_array('5031', $allowedContents) && !in_array("$xhtmlContNt", $allowedContents)) {
            $response->setStatus(APIResponse::ERROR)->setMessage("The supplied node does not allow to have structured document container as a child");
            return $response;
        }

        $channels = $request->input('channels', "");
        if ( empty($channels) ) {

            // Getting channels for the node (in node properties or all registered channels)
            $channel = new Channel();
            $channelsNode = $channel->getChannelsForNode($idNode);
            $channels = array();
            foreach ($channelsNode as $channelNode) {
                array_push($channels, $channelNode['IdChannel']);
            }
        }

        /* Split the supplied channels using the SEPARATOR */ else {

            $channels = explode(self::SEPARATOR, $channels);
        }

        $name = $request->input('name', "");
        if ( empty($name) ) {
            $response->setStatus(APIResponse::ERROR)->setMessage('Parameter name is missing');
            return $response;
        }
        $tName = $name;
        $ext = 1;
        while($node->GetChildByName($tName)){
            $tName = $name . '_' . $ext;
            $ext++;
        }
        $name = $tName;

        $idTemplate = $request->input('id_schema', "");
        if ( empty($idTemplate) ) {
            $response->setStatus(APIResponse::ERROR)->setMessage('Parameter id_schema is missing');
            return $response;
        }

        /* The result of the action to be put in the response */
        $actionResult = array();

        // Creating container
        $baseIoInferer = new BaseIOInferer();
        $inferedNodeType = $baseIoInferer->infereType('FOLDER', $idNode);
        $nodeType = new NodeType();
        $nodeType->SetByName($inferedNodeType['NODETYPENAME']);
        if (!($nodeType->get('IdNodeType') > 0)) {
            $response->setStatus(APIResponse::ERROR)->setMessage('A nodetype could not be estimated to create the container folder, operation will be aborted, contact with your administrator');
            return $response;
        }

        $data = array(
            'NODETYPENAME' => $nodeType->get('Name'),
            'NAME' => $name,
            'PARENTID' => $idNode,
            'FORCENEW' => true,
            'CHILDRENS' => array(
                array('NODETYPENAME' => 'VISUALTEMPLATE', 'ID' => $idTemplate)
            )
        );
        $username = $request->get(self::USER_PARAM);
        $user = new User();
        $user->setByLogin($username);
        $user_id = $user->GetID();

        $baseIO = new baseIO();
        $idContainer = $result = $baseIO->build($data, $user_id);

        if (!($result > 0)) {
            $errorMessage = 'An error ocurred creating the container node.';
            foreach ($baseIO->messages->messages as $message) {
                $errorMessage .= " " . $message . ".";
            }

            $response->setStatus(APIResponse::ERROR)->setMessage($errorMessage);
            return $response;
        }

        $actionResult['container_nodeid'] = $idContainer;

        $languages = $request->get('languages', false, "");
        if ( empty($languages) ) {
            $language = new Language();
            $languagesNodes = $language->getLanguagesForNode($idNode);
            $languages = array();
            foreach ($languagesNodes as $languageNode) {
                $lang = new Language($languageNode['IdLanguage']);
                array_push($languages, $lang->GetIsoName());
            }
        } else {
            $languages = explode(self::SEPARATOR, $languages);
        }


        if ($result && is_array($languages)) {
            $baseIoInferer = new BaseIOInferer();
            $inferedNodeType = $baseIoInferer->infereType('FILE', $idContainer);
            $nodeType = new NodeType();
            $nodeType->SetByName($inferedNodeType['NODETYPENAME']);
            if (!($nodeType->get('IdNodeType') > 0)) {
                $response->setStatus(APIResponse::ERROR)->setMessage('A nodetype could not be estimated to create the document, operation will be aborted, contact with your administrator');
                return $response;
            }

            foreach ($channels as $idChannel) {
                $formChannels[] = array('NODETYPENAME' => 'CHANNEL', 'ID' => $idChannel);
            }

            // structureddocument inserts content document
            $setSymLinks = array();
            $master = $request->get('master', false, "");

            foreach ($languages as $isoLanguage) {
                $result = $this->_insertLanguage($isoLanguage, $nodeType->get('Name'), $name, $idContainer, $idTemplate, $formChannels);

                if ($result == NULL) {
                    /* If any error occurred when inserting the languages, stop the process and removes the container created previously */
                    $containerNode = new Node($idContainer);
                    $containerNode->DeleteNode();
                    $response->setStatus(APIResponse::ERROR)->setMessage(sprintf('Insertion of document %s with language %s has failed', $name, $isoLanguage));
                    return;
                }
                if ( !empty($master) ) {
                    if ($master != $isoLanguage) {
                        $setSymLinks[] = $result;
                    } else {
                        $idNodeMaster = $result;
                    }
                }

                $insertedNode = new Node($result);
                $actionResult['container_langs'][$isoLanguage] = array('nodeid' => $result, 'nodename' => $insertedNode->get('Name'));
            }

            foreach ($setSymLinks as $idNodeToLink) {
                $structuredDocument = new StructuredDocument($idNodeToLink);
                $structuredDocument->SetSymLink($idNodeMaster);

                $slaveNode = new Node($idNodeToLink);
                $slaveNode->set('SharedWorkflow', $idNodeMaster);
                $slaveNode->update();
            }
        }

        /* Creates the response using ResponseBuilder (which already contains the $response object param) */
        return $response->setResponse($actionResult);
    }

    /**
     * <p>Gets the possible RNG schemas for a given node which can be used to create a new XML container</p>
     * @param $request the current request
     * @param $response the response
     */
    public function schemas(Request $request ) {
        $response = new APIResponse;

        $idNode = $request->input('nodeid', "");

        if( !empty($idNode) ){
            $node = new Node($idNode);
            if (!$this->checkParameters($request, $response)) {
                return $response;
            }
            $idproject = $node->GetProject();
            $project = new Node($idproject);
            $p_name = $project->GetNodeName();
            $schemas[$p_name] = $node->getSchemas();

        } else {
            //starting on the main root Ximdex node.
            $idNode = 10000;
            $node = new Node($idNode);
            $projects = $node->GetChildren(5013);

            $schemas = array();

            if (!is_null($projects)) {
                foreach($projects as $idproject){
                    $p = new Node($idproject);
                    $p_name=$p->GetNodeName();
                    $schemas[$p_name]=$p->getSchemas();
                }
            }
        }

        $schemaArray = array();
        if (!is_null($schemas)) {
            foreach ($schemas as $p_name => $project) {
                foreach ($project as $idschema) {
                    $schemaNode = new Node($idschema);
                    $schemaArray[$p_name][] = array('idschema' => $idschema, 'Name' => $schemaNode->get('Name'));
                }
            }
        }

        return $response->setResponse($schemaArray);
    }

    /**
     * <p>Inserts given language as a child of the container</p>
     * @param type $isoName the language iso name
     * @param type $nodeTypeName the name of the node type
     * @param type $name
     * @param type $idContainer
     * @param type $idTemplate
     * @param type $formChannels
     * @return type
     */
    public function _insertLanguage($isoName, $nodeTypeName, $name, $idContainer, $idTemplate, $formChannels) {
        $language = new Language();
        $language->SetByIsoName($isoName);

        if (!($language->get('IdLanguage') > 0)) {
            return NULL;
        }

        $idLanguage = $language->get('IdLanguage');

        $data = array(
            'NODETYPENAME' => $nodeTypeName,
            'NAME' => $name,
            'PARENTID' => $idContainer,
            "CHILDRENS" => array(
                array("NODETYPENAME" => "VISUALTEMPLATE", "ID" => $idTemplate),
                array("NODETYPENAME" => "LANGUAGE", "ID" => $idLanguage)
            )
        );

        foreach ($formChannels as $channel) {
            $data['CHILDRENS'][] = $channel;
        }

        $baseIO = new baseIO();
        $result = $baseIO->build($data);
        if ($result > 0) {
            return $result;
        } else {
            return NULL;
        }
    }

    /**
     * <p>Sets the content of the node</p>
     * @param type $request
     * @param type $response
     * @return type
     */
    public function contentxml(Request $request ) {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'content' => 'required' ] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'Parameter content is missing or invalid' );
        }

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $idnode = $request->input('nodeid', "");
        $content = $request->input('content');
        $validate = $request->input('validate', false);

        $content = rawurldecode(stripslashes($content));
        $content = str_replace(" ","+",$content);
        $content = base64_decode($content, true);

        $node = new Node($idnode);

        if($node->GetNodeType() == NodeTypeID::XHTML5_DOC){
            $utils = new HTMLEditorUtils();
            $content = $utils->setContentFromEditor($content);
        }

        /* Check whether the supplied node Id references to an XML document */
        if ($node->GetNodeType() != NodeTypeID::XML_DOCUMENT && $node->GetNodeType() != NodeTypeID::XHTML5_DOC) {
            $response->setStatus(APIResponse::ERROR)->setMessage("The supplied node id does not refer to an structured document");
            return $response;
        }

        if($validate){
            /* Check whether the document is compliant with the schema */
            $idcontainer = $node->getParent();
            $reltemplate = new RelTemplateContainer();
            $idTemplate = $reltemplate->getTemplate($idcontainer);

            $templateNode = new Node($idTemplate);
            $templateContent = $templateNode->GetContent();

            $contentToValidate = "<docxap>" . $content . "</docxap>";

            $validator = new \Ximdex\XML\Validators\RNG();
            $result = $validator->validate($templateContent, $contentToValidate);

            if (!$result) {
                $response->setStatus(APIResponse::ERROR)->setMessage('The content of the document does not match with the schema ' . $templateNode->GetNodeName());
                return $response;
            }
        }

        $node->SetContent($content, true);
        $node->setState("7");
        return $response->setResponse('Content updated successfully');
    }

    /**
     *
     * @param $request the current request
     * @param $response the response
     */
    public function publish(Request $request ) {
        $response = new APIResponse;

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $idnode = $request->input('nodeid', "");
        $uptime = time();

        // TODO: Refactor this!
        $userName = $request->input(self::USER_PARAM);
        $user = new User();
        $user->setByLogin($userName);
        $userID = $user->getID();
        Session::set('userID', $userID);

        $flagsPublication = array(
            'markEnd' => 0,//$markEnd,
            'structure' => 1,
            'deeplevel' => 1,
            'force' => 1,
            'recurrence' => true,
            'workflow' => true,
            'lastPublished' => 0
        );

        //Move the node to next state
        $this->promoteNode($idnode, 8);

        $node = new Node($idnode);

        $result = SynchroFacade::pushDocInPublishingPool($idnode, $uptime, null, $flagsPublication);

        if (empty($result)) {
            $response->setResponse('This node does not need to be published again');
        } else {
            $response->setResponse('Node ' . $idnode . " added to the publishing queue");
        }

        return $response;
    }

    private function promoteNode($idNode, $idState) {

        $idUser = Session::get("userID");
        $node = new Node($idNode);
        $idActualState = $node->get('IdState');
        $actualWorkflowStatus = new WorkFlow($idNode, $idActualState);

        $idTransition = $actualWorkflowStatus->pipeProcess->getTransition($idActualState);
        $transition = new \Ximdex\Models\PipeTransition($idTransition);
        $callback = $transition->get('Callback');

        $viewPath = App::getValue('AppRoot') . sprintf('/inc/repository/nodeviews/View_%s.class.php', $callback);
        if (!empty($callback) && is_file($viewPath)) {
            $dataFactory = new DataFactory();
            $idVersion = $dataFactory->GetLastVersionId();
            $transformedContent = $transition->generate($idVersion, $node->GetContent(), array());
            $node->SetContent($transformedContent);
        }

        $result = $node->setState($idState);
    }


    /**
     *
     * @param $request the current request
     * @param $response the response
     */
    public function getcontent(Request $request ) {
        $response = new APIResponse;

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $idnode = $request->input('nodeid', "");
        $clean = $request->input('clean', false);

        $node = new Node($idnode);
        $content = $node->GetContent();
        if($clean){
            $content = preg_replace('/(\v|\s)+/', ' ', $content);
        }
        if($node->GetNodeType() == NodeTypeID::XHTML5_DOC){
            $utils = new HTMLEditorUtils();
            $content = $utils->getContentToEditor($content);
        }

        if (empty($content)) {
            $response->setStatus(APIResponse::ERROR)->setMessage("The content of the given node couldn't be successfully retrieved.");
        } else {
            if($node->GetNodeType() == NodeTypeID::XML_DOCUMENT){

            }
            $response->setResponse($content);
        }

        return $response;
    }

    /**
     *
     * @param $request the current request
     * @param $response the response
     */
    public function delete($request, $response){
        if (!$this->checkParameters($request, $response)) {
            return;
        }
        $idnode = $request->input('nodeid', "");
        $node = new Node($idnode);
        $result = $node->delete();

        if (!$result) {
            $response->setStatus(APIResponse::ERROR)->setMessage("This node couldn't be deleted.");
        } else {
            $response->SetMessage('Node ' . $idnode . " successfully deleted.");
        }

        return $response;
    }
}