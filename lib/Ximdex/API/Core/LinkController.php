<?php
namespace Ximdex\API\Core;

use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Ximdex\API\APIResponse;
use Ximdex\Models\Link as LinkModel;

\ModulesManager::file('/inc/io/BaseIO.class.php');

class LinkController extends Controller  {

    public function create(Request $request)
    {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'nodeid' => 'required',  'name' => 'required', 'url' => 'required' ] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'Some required parameters are missing' );
        }

        $parentId = $request->input("nodeid");
        $name = $request->input("name");
        $description = $request->input("description", "");
        $url = $request->input("url");

        $b64decoded = base64_decode($description, true);
        $description = $b64decoded === false ? urldecode(stripslashes($content)) : urldecode($b64decoded);

        $id = $this->createNodeLink($name, $url, $description, $parentId);
        if ($id <= 0) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("An error ocurred creating the link.");
        }
        $respContent = array('nodeid' => $id);
        return $response->setResponse($respContent);
    }


    protected function createNodeLink($name, $url, $description, $idParent)
    {

        $data = array('NODETYPENAME' => 'LINK',
            'NAME' => $name,
            'PARENTID' => $idParent,
            'IDSTATE' => 0,
            'CHILDRENS' => array(
                array('URL' => $url),
                array('DESCRIPTION' => $description)
            )
        );

        $bio = new \baseIO();
        $result = $bio->build($data);

        if ($result > 0) {
            $link = new LinkModel($result);
            $link->set('ErrorString', 'not_checked');
            $link->set('CheckTime', time());
            $link->update();
        }
        return $result;
    }
}