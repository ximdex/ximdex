<?php
namespace Ximdex\API;

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

        $app = Application::getInstance();

        $app['auth']->viaRequest('api', function (Request $request) {
            $response = new APIResponse();

            if ($request->input('ximtoken')) {
                $encryptedXimtoken = $request->input('ximtoken');

                $tokenService = new \Ximdex\Services\Token();

                $ximtoken = $tokenService->decryptToken($encryptedXimtoken, App::getValue("ApiKey"), App::getValue("ApiIV"));

                if ($ximtoken == null) {
                    return null;
                }

                if ($tokenService->hasExpired($ximtoken)) {
                    return null;
                }

                $username = $ximtoken['user'];
                // Is a valid user !
                $user = new User();
                $user->setByLogin($username);
                $user_id = $user->GetID();
                if ($user_id == null) {
                    return null;
                }

                return $user;
            }
            return null;
        });

        $app->get('api/login', ['uses' => 'Ximdex\API\Core\LoginController@index']);
        $app->post('api/login', ['uses' => 'Ximdex\API\Core\LoginController@index']);

        $app->group([ 'prefix' => 'api', 'middleware' => 'auth', 'namespace' => 'Ximdex\API\Core' ], function () use ($app) {

            // Language
            $app->get('lang', [ 'uses' => 'LanguageController@index' ]);
            $app->post('lang', [ 'uses' => 'LanguageController@index' ]);

            // Channel
            $app->get('channel', [ 'uses' => 'ChannelController@index' ]);
            $app->post('channel', [ 'uses' => 'ChannelController@index' ]);

            $app->get('channel/node', [ 'uses' => 'ChannelController@index' ]);
            $app->post('channel/node', [ 'uses' => 'ChannelController@index' ]);

            // Nodetype
            $app->get('nodetype', [ 'uses' => 'NodetypeController@index' ]);
            $app->post('nodetype', [ 'uses' => 'NodetypeController@index' ]);

            // Node
            $app->get('node', [ 'uses' => 'NodeController@index' ]);
            $app->post('node', [ 'uses' => 'NodeController@index' ]);

            $app->get('node/create', [ 'uses' => 'NodeController@create' ]);
            $app->post('node/create', [ 'uses' => 'NodeController@create' ]);

            $app->get('node/content', [ 'uses' => 'NodeController@content' ]);
            $app->post('node/content', [ 'uses' => 'NodeController@content' ]);

            $app->get('node/info', [ 'uses' => 'NodeController@info' ]);
            $app->post('node/info', [ 'uses' => 'NodeController@info' ]);

            $app->get('node/schemas', [ 'uses' => 'NodeController@schemas' ]);
            $app->post('node/schemas', [ 'uses' => 'NodeController@schemas' ]);

            $app->get('node/createxml', [ 'uses' => 'NodeController@createxml' ]);
            $app->post('node/createxml', [ 'uses' => 'NodeController@createxml' ]);

            $app->get('node/contentxml', [ 'uses' => 'NodeController@contentxml' ]);
            $app->post('node/contentxml', [ 'uses' => 'NodeController@contentxml' ]);

            $app->get('node/publish', [ 'uses' => 'NodeController@publish' ]);
            $app->post('node/publish', [ 'uses' => 'NodeController@publish' ]);

            $app->get('node/delete', [ 'uses' => 'NodeController@delete' ]);
            $app->post('node/delete', [ 'uses' => 'NodeController@delete' ]);

            // Search
            $app->get('search', [ 'uses' => 'SearchController@index' ]);
            $app->post('search', [ 'uses' => 'SearchController@index' ]);

            // Preview
            $app->get('preview', [ 'uses' => 'PreviewController@index' ]);
            $app->post('preview', [ 'uses' => 'PreviewController@index' ]);

            // Links
            $app->get('link/create', [ 'uses' => 'LinkController@create' ]);
            $app->post('link/create', [ 'uses' => 'LinkController@create' ]);
        });


    }
}