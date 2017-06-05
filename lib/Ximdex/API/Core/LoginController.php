<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 13/02/17
 * Time: 13:52
 */

namespace Ximdex\API\Core;

use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Ximdex\API\APIResponse;
use Ximdex\Authenticator;
use Ximdex\Runtime\App;
use Ximdex\Services\Token;

\ModulesManager::file('/inc/i18n/I18N.class.php');

class LoginController extends Controller  {

    /**
     * <p>Default method for this action</p>
     * <p>Executes the login check method</p>
     * @param Request The current request
     * @param Response The Response object to be sent and where to put the response of this action
     */
    public function index( Request $request ) {

        $response = new APIResponse;

        try {
            $this->validate( $request, [ 'user' => 'required', 'pass' => 'required' ] );
        } catch (ValidationException $e) {
            return $response->setStatus( APIResponse::ERROR )->setMessage( 'Bad parameters. user or pass parameters are missing' );
        }

        $user = $request->input('user');
        $pass = $request->input('pass');

        $authenticator = new Authenticator();
        $success = $authenticator->login($user, $pass);

        if ($success) {
            $responseContent = array('ximtoken' => $this->generateXimToken($user));
            $response->setResponse($responseContent);
        } else {
            $response->setStatus(APIResponse::ERROR)->setMessage('Incorrect login parameters');
        }

        return $response;
    }

    /**
     * <p>Generates a Ximdex token to be used in subsequently requests to the Ximdex API</p>
     * @param string $user the user for which to generate the token
     * @return string the generated token
     */
    private function generateXimToken($user) {
        $tokenService = new Token();
        $token = $tokenService->getToken($user, App::getValue( 'TokenTTL'));
        return $token;
    }

}