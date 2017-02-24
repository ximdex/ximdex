<?php
namespace Ximdex\API;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteGroup;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Laravel\Lumen\Application;
use Ximdex\Authenticator;
use Ximdex\Models\User;
use Ximdex\Runtime\App;


class Manager {
    // rutas del API
    public static function addApiRoutes()
    {
        self::setupAuthMiddlewares();

        self::setupRoutes();
    }

    /**
     * @param $app
     */
    private static function setupAuthMiddlewares() {
        $app = Application::getInstance();

        $app->routeMiddleware([
            'auth' => Authenticate::class,
            'apicreate' => \Ximdex\API\Middleware\AuthCreateMiddleware::class,
            'apiread' => \Ximdex\API\Middleware\AuthReadMiddleware::class,
            'apiupdate' => \Ximdex\API\Middleware\AuthUpdateMiddleware::class,
            'apidelete' => \Ximdex\API\Middleware\AuthDeleteMiddleware::class,
            'apipublish' => \Ximdex\API\Middleware\AuthPublishMiddleware::class,
        ]);

        $app[ 'auth' ]->viaRequest( 'api', function (Request $request) {
            if ( $request->input( 'ximtoken' ) ) {
                $encryptedXimtoken = $request->input( 'ximtoken' );

                $tokenService = new \Ximdex\Services\Token();

                $ximtoken = $tokenService->decryptToken( $encryptedXimtoken, App::getValue( "ApiKey" ), App::getValue( "ApiIV" ) );

                if ( empty($ximtoken) ) {
                    return null;
                }

                if ( $tokenService->hasExpired( $ximtoken ) ) {
                    return null;
                }

                $username = $ximtoken[ 'user' ];
                // Is a valid user !
                $user = new User();
                $user->setByLogin( $username );
                $user_id = $user->getID();
                if ( empty($user_id) ) {
                    return null;
                }
                $request['userID'] = $user_id;
                $request['userLogged'] = $user->getLogin();
                return $user;
            }
            return null;
        } );
    }

    /**
     * @param $app
     */
    private static function setupRoutes() {
        $app = Application::getInstance();

        $app->get( 'api/login', [ 'uses' => 'Ximdex\API\Core\LoginController@index' ] );
        $app->post( 'api/login', [ 'uses' => 'Ximdex\API\Core\LoginController@index' ] );

        $app->group( [ 'prefix' => 'api', 'middleware' => 'auth', 'namespace' => 'Ximdex\API\Core' ], function () use ($app) {

            // Language
            $app->get( 'lang', [ 'middleware' => ['apiread'], 'uses' => 'LanguageController@index' ] );
            $app->post( 'lang', [ 'middleware' => ['apiread'], 'uses' => 'LanguageController@index' ] );

            // Channel
            $app->get( 'channel', [ 'middleware' => ['apiread'], 'uses' => 'ChannelController@index' ] );
            $app->post( 'channel', [ 'middleware' => ['apiread'], 'uses' => 'ChannelController@index' ] );

            $app->get( 'channel/node', [ 'middleware' => ['apiread'], 'uses' => 'ChannelController@index' ] );
            $app->post( 'channel/node', [ 'middleware' => ['apiread'], 'uses' => 'ChannelController@index' ] );

            // Nodetype
            $app->get( 'nodetype', [ 'middleware' => ['apiread'], 'uses' => 'NodetypeController@index' ] );
            $app->post( 'nodetype', [ 'middleware' => ['apiread'], 'uses' => 'NodetypeController@index' ] );

            // Node
            $app->get( 'node', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@index' ] );
            $app->post( 'node', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@index' ] );

            $app->get( 'node/create', [ 'middleware' => ['apicreate'], 'uses' => 'NodeController@create' ] );
            $app->post( 'node/create', [ 'middleware' => ['apicreate'], 'uses' => 'NodeController@create' ] );

            $app->get( 'node/content', [ 'middleware' => ['apiupdate'], 'uses' => 'NodeController@content' ] );
            $app->post( 'node/content', [ 'middleware' => ['apiupdate'], 'uses' => 'NodeController@content' ] );

            $app->get( 'node/info', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@info' ] );
            $app->post( 'node/info', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@info' ] );

            $app->get( 'node/schemas', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@schemas' ] );
            $app->post( 'node/schemas', [ 'middleware' => ['apiread'], 'uses' => 'NodeController@schemas' ] );

            $app->get( 'node/createxml', [ 'middleware' => ['apicreate'], 'uses' => 'NodeController@createxml' ] );
            $app->post( 'node/createxml', [ 'middleware' => ['apicreate'], 'uses' => 'NodeController@createxml' ] );

            $app->get( 'node/contentxml', [ 'middleware' => ['apiupdate'], 'uses' => 'NodeController@contentxml' ] );
            $app->post( 'node/contentxml', [ 'middleware' => ['apiupdate'], 'uses' => 'NodeController@contentxml' ] );

            $app->get( 'node/publish', [ 'middleware' => ['apipublish'], 'uses' => 'NodeController@publish' ] );
            $app->post( 'node/publish', [ 'middleware' => ['apipublish'], 'uses' => 'NodeController@publish' ] );

            $app->get( 'node/delete', [ 'middleware' => ['apidelete'], 'uses' => 'NodeController@delete' ] );
            $app->post( 'node/delete', [ 'middleware' => ['apidelete'], 'uses' => 'NodeController@delete' ] );

            // Search
            $app->get( 'search', [ 'middleware' => ['apiread'], 'uses' => 'SearchController@index' ] );
            $app->post( 'search', [ 'middleware' => ['apiread'], 'uses' => 'SearchController@index' ] );

            // Preview
            $app->get( 'preview', [ 'middleware' => ['apiread'], 'uses' => 'PreviewController@index' ] );
            $app->post( 'preview', [ 'middleware' => ['apiread'], 'uses' => 'PreviewController@index' ] );

            // Links
            $app->get( 'link/create', [ 'middleware' => ['apicreate'], 'uses' => 'LinkController@create' ] );
            $app->post( 'link/create', [ 'middleware' => ['apicreate'], 'uses' => 'LinkController@create' ] );
        } );
    }
}