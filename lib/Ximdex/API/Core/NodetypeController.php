<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 14/02/17
 * Time: 11:04
 */

namespace Ximdex\API\Core;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Ximdex\API\APIResponse;

require_once(XIMDEX_ROOT_PATH . '/conf/stats.php');
ModulesManager::file('/inc/i18n/I18N.class.php');

class NodetypeController extends Controller {

    /**
     * <p>Default method for this action</p>
     * <p>Gets all registered nodetypes or a specific nodetype</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function index( Request $request)
    {
        $response = new APIResponse;

        $nodeTypeId = $request->input("nodetypeid", "");
        if ( empty($nodeTypeId) ) {
            $nodeTypes = $this->getNodeTypeInfo();
        } else {
            $nodeTypeIdInt = intval($nodeTypeId);
            if ($nodeTypeIdInt == 0) {
                return $response->setStatus(APIResponse::ERROR)->setMessage("Bad identifier supplied");
            }

            $nodeTypes = $this->getNodeTypeInfo($response, $nodeTypeIdInt);
        }

        if (empty($nodeTypes)) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("No nodetypes found");
        }

        return $response->setResponse($nodeTypes);
    }

    /**
     * <p>Gets the registered nodetypes or a specific nodetype if a nodeType id is given</p>
     * @param int $nodeType The nodeType id
     * @return array containing the requested nodetypes
     */
    private function getNodeTypeInfo(Response $response, $nodeType = null)
    {
        $where = $nodeType == null || $nodeType == "" ? "" : " WHERE n.IdNodeType = " . $nodeType;
        $sql = "SELECT n.IdNodeType, n.Name, n.Description, r.extension from NodeTypes n join RelNodeTypeMimeType r on(n.IdNodeType=r.IdNodeType)" . $where;
        $dbObj = new DB();
        $dbObj->Query($sql);
        if ($dbObj->numErr != 0) {
            $response->setStatus(APIResponse::ERROR)->setMessage('An error ocurred while processing');
            return;
        }

        $nodeTypes = array();
        while (!$dbObj->EOF) {
            $nodeTypes[] = array('idnodetype' => $dbObj->getValue("IdNodeType"),
                'name' => $dbObj->getValue("Name"),
                'description' => $dbObj->getValue("Description"),
                'extension' => $dbObj->getValue("extension")
            );
            $dbObj->Next();
        }

        return $nodeTypes;
    }
}