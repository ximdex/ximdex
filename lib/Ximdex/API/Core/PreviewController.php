<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 14/02/17
 * Time: 11:10
 */

namespace Ximdex\API\Core;


use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ximdex\API\APIResponse;
use Ximdex\Models\Channel;
use Ximdex\Models\Node;
use Ximdex\Models\StructuredDocument;
use Ximdex\Models\User;
use Ximdex\Services\Node as NodeService;
use Ximdex\Utils\FsUtils;
use Ximdex\Utils\PipelineManager;

ModulesManager::file('/inc/utils.php');
ModulesManager::file('/inc/filters/Filter.class.php');
ModulesManager::file('/inc/repository/nodeviews/View_NodeToRenderizedContent.class.php');
ModulesManager::file('/inc/repository/nodeviews/View_PrefilterMacros.class.php');
ModulesManager::file('/inc/repository/nodeviews/View_Dext.class.php');
ModulesManager::file('/inc/repository/nodeviews/View_Xslt.class.php');
ModulesManager::file('/inc/repository/nodeviews/View_FilterMacrosPreview.class.php');

class PreviewController extends Controller  {

    /**
     * <p>Default method for this action</p>
     * <p>Renders a XML file into the browser</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function index(Request $request)
    {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'nodeid' => 'required',  'channelid' => 'required' ] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'Some required parameters are missing' );
        }

        // Initializes variables
        $args = array();

        // Receives request params
        $idnode = $request->input("nodeid", "");
        $idchannel = $request->input("channelid", "");
        $json = $request->input("json", false);

        if ( empty($json) ) {
            $json = 0;
        }

        if (empty($idchannel)) {
            $idchannel = $request->input("channel", "");
        }

        if (!$this->checkParameters($request, $response)) {
            return $response;
        }

        $node = new Node($idnode);

        // Checks if node is a structured document
        $structuredDocument = new StructuredDocument($idnode);
        if (!($structuredDocument->get('IdDoc') > 0)) {
            $response->setStatus(APIResponse::ERROR)->setMessage("It is not possible to show preview. Provided node is not a structured document.");
            return $response;
        }

        // Checks content existence
        $content = $node->GetContent();
        if (!$content) {
            $content = $structuredDocument->GetContent($request->input('version', ''), $request->input('sub_version', ''));
        } else {
            //$content = $this->_normalizeXmlDocument($content);
        }

        // Validates channel
        if (!is_numeric($idchannel)) {
            $channels = $node->getChannels();
            $firstChannel = null;
            $idchannel = NULL;
            if (!empty($channels)) {
                foreach ($channels as $c) {
                    $c = new Channel($c);
                    $cName = $c->getName();
                    $ic = $c->get('IdChannel');
                    if ($firstChannel === null) $firstChannel = $ic;
                    if (strToUpper($cName) == 'HTML') $idchannel = $ic;
                    unset($c);
                }
            }
            if ($idchannel === null) $idchannel = $firstChannel;

            if ($idchannel === null) {
                $response->setStatus(APIResponse::ERROR)->setMessage("It is not possible to show preview. There isn't any defined channel.");
                return $response;
            }
        }

        // Populates variables and view/pipelines args
        // TODO: if node does not exist receive rest of params by request
        $idSection = $node->GetSection();
        $idProject = $node->GetProject();
        $idServerNode = $node->getServer();
        $documentType = $structuredDocument->getDocumentType();
        $idLanguage = $structuredDocument->getLanguage();
        $docXapHeader = null;
        if (method_exists($node->class, "_getDocXapHeader")) {
            $docXapHeader = $node->class->_getDocXapHeader($idchannel, $idLanguage, $documentType);
        }
        $nodeName = $node->get('Name');
        $depth = $node->GetPublishedDepth();

        $args['MODE'] = $request->input('mode', '') == 'dinamic' ? 'dinamic' : 'static';
        $args['CHANNEL'] = $idchannel;
        $args['SECTION'] = $idSection;
        $args['PROJECT'] = $idProject;
        $args['SERVERNODE'] = $idServerNode;
        $args['LANGUAGE'] = $idLanguage;
        $args['DOCXAPHEADER'] = $docXapHeader;
        $args['NODENAME'] = $nodeName;
        $args['DEPTH'] = $depth;
        $args['DISABLE_CACHE'] = true;
        $args['CONTENT'] = $content;
        $args['NODETYPENAME'] = $node->nodeType->get('Name');

        $idnode = $idnode > 10000 ? $idnode : 10000;
        $node = new Node($idnode);

        $transformer = $node->getProperty('Transformer');
        $args['TRANSFORMER'] = $transformer[0];
        // Process Structured Document -> dexT/XSLT:
        $pipelineManager = new PipelineManager();

        $content = $pipelineManager->getCacheFromProcess(NULL, 'StrDocToDexT', $args);
        // Specific FilterMacros View for previsuals:
        $viewFilterMacrosPreview = new View_FilterMacrosPreview();
        $file = $viewFilterMacrosPreview->transform(NULL, $content, $args);

        if ($json == true) {
            $content = FsUtils::file_get_contents($file);
            $response->setResponse($content);
        } else {
            $response = response();
            $response->withHeaders([
                'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
                'Last-Modified' => gmdate("D, d M Y H:i:s") . " GMT",
                'Cache-Control' => array('no-store, no-cache, must-revalidate', 'post-check=0, pre-check=0'),
                'Pragma' => 'no-cache',
                'Content-type' => 'text/html',
            ]);
            $content = FsUtils::file_get_contents($file);
            $response->setContent($content);
        }
        return $response;
    }

    /**
     * <p>Checks whether the required parameters are present in the request
     * and modifies the response accordingly</p>
     *
     * @param $request the request
     * @param $response the response
     * @return true if all required parameters are present and valid and false otherwise
     */
    private function checkParameters(Request $request)
    {
        $response = new APIResponse;

        $nodeid = $request->input('nodeid', "");

        /* @var $user User */
        $user = $request->user();
        $username = $user->getLogin();

        $node = new Node($nodeid);

        if ( empty($nodeid) ) {
            $response->setStatus(APIResponse::ERROR)->setMessage('The nodeid parameter is missing');
            return false;
        }
        if ( empty($node->GetID()) ) {
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

}