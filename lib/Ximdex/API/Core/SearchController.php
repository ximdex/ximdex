<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 14/02/17
 * Time: 11:21
 */

namespace Ximdex\API\Core;



use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Ximdex\API\APIResponse;
use Ximdex\Models\Node;
use Ximdex\Models\User;

class SearchController extends Controller  {

    /**
     * <p>Default method for this action</p>
     * <p>search info of the node passed as parameter</p>
     * @param $request The current request
     * @param $response The response object to be sent and where to put the response of this action
     */
    public function index( Request $request )
    {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'name' => 'required' ] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'The name parameter is missing' );
        }

        $nodename = $request->input('name');

        $nodeInfo = $this->getNodeInfo($nodename);

        return $response->setResponse($nodeInfo);
    }

    /**
     * <p>Gets the node info</p>
     * <p>It will return the following properties of the node:
     *  <ul>
     *      <li>Nodeid</li>
     *      <li>Name</li>
     *      <li>Icon</li>
     *      <li>Children</li>
     *  </ul>
     * </p>
     *
     * @param string $nodename the node name to get the information
     * @return array containing the node information
     */
    private function getNodeInfo($nodename)
    {
        $node = new Node($nodename);
        $nodeInfo = $node->GetByName($nodename);
        foreach ($nodeInfo as $info) {
            if (strcmp($info["Icon"], 'action.png') !== 0) {
                $res[] = $info;
            }
        }
        return $res;
    }

}