<?php
namespace Ximdex\API\Core;

use Laravel\Lumen\Routing\Controller;
use Ximdex\API\APIResponse;
use Illuminate\Http\Request;
use Ximdex\Models\Language as LanguageModel;
use Ximdex\Models\Node;
use Ximdex\Models\User;
use Ximdex\Services\Node as NodeService;

class LanguageController extends Controller   {
    /**
     * <p>Default method for this action</p>
     * <p>Gets all registered languages or a specific one</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function index( Request $request )
    {
        $response = new APIResponse;

        $langId = $request->get("langid", true, "");

        if ( empty($langId) ) {
            $langs = $this->getLanguageInfo();
        } else {
            $l = new LanguageModel($langId);
            if ($l->GetID() == null) {
                return $response->setStatus(APIResponse::ERROR)->setMessage("The language ID given is not a existing language.");
            }

            $langs = $this->getLanguageInfo($l->GetID());
        }

        if (empty($langs)) {
            return $response->setStatus(APIResponse::ERROR)->setMessage("No languages found");
        }

        return $response->setResponse($langs);
    }

    /**
     * <p>Gets the valid languages for the given node</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function node( Request $request )
    {
        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'nodeid' => 'required'] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'The nodeid parameter is missing' );
        }

        $nodeid = $request->input('nodeid');

        /* @var $user User */
        $user = $request->user();

        $username = $user->getLogin();

        $node = new Node($nodeid);

        if ( empty($node->GetID()) ) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('The node ' . $nodeid . ' does not exist');
        }

        $nodeService = new NodeService();

        $hasPermissionOnNode = $nodeService->hasPermissionOnNode($username, $nodeid);

        if (!$hasPermissionOnNode) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('The user does not have permission on node ' . $nodeid);
        }

        $lang = new LanguageModel();
        $langs = $lang->getLanguagesForNode($nodeid);

        if ( empty($langs) ) {
            return $response->setStatus(APIResponse::ERROR)->setMessage('No languages found for the node');
        }

        return $response->setResponse($langs);
    }

    /**
     * <p>Gets the registered languages or a specific language if a language id is given</p>
     * @param int $lang The lang id
     * @return array containing the requested languages
     */
    private function getLanguageInfo($langId = null)
    {

        $lang = new LanguageModel();
        $langs = array();
        if ( !empty($langId) ) {
            $lang->SetID($langId);
            $langItem = array(
                'IdLanguage' => $langId,
                'Name' => $lang->get('Name'),
                'IsoCode' => $lang->get('IsoName')
            );
            array_push($langs, $langItem);
        } else {
            $langsIds = $lang->GetAllLanguages();
            foreach ($langsIds as $langItemId) {
                $l = new LanguageModel($langItemId);
                $langItem = array(
                    'IdLanguage' => $l->get('IdLanguage'),
                    'Name' => $l->get('Name'),
                    'IsoName' => $l->get('IsoName')
                );
                array_push($langs, $langItem);
            }
        }
        return $langs;
    }
}