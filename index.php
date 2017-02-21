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
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 *
 */


use Ximdex\MVC\FrontController;
use Ximdex\Runtime\App;
use Ximdex\Utils\FsUtils;

include_once 'bootstrap/start.php';

/**
 * Dispatch XIMDEX_START event
 */
App::dispatchEvent(\Ximdex\Events::XIMDEX_START);

ModulesManager::file('/inc/utils.php');
ModulesManager::file('/inc/io/BaseIO.class.php');
ModulesManager::file('/inc/mvc/App.class.php');
ModulesManager::file('/inc/i18n/I18N.class.php');
ModulesManager::file('/inc/install/InstallController.class.php');

function goLoadAction()
{
    header(sprintf("Location: %s", App::getValue('UrlRoot')));
}

//Main thread
if (!InstallController::isInstalled()) {
    header( 'Location: ./setup/index.php');
    die();


} else {
    $locale = \Ximdex\Utils\Session::get('locale');
    I18N::setup($locale); // Check coherence with HTTP_ACCEPT_LANGUAGE*/

    // TODO: Move this logic outta here

    $app = new Laravel\Lumen\Application();

    $app->configureMonologUsing(function($monolog) {
        $monolog->pushHandler(\Ximdex\Logger::get()->getHandlers()[0]);

        return $monolog;
    });

    $app->routeMiddleware([
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'checkuserlogged' => \Ximdex\MVC\Middleware\CheckUserLoggedMiddleware::class,
    ]);

    //$app->alias('request', '\Ximdex\Runtime\WebRequest');

    \Ximdex\API\Manager::addApiRoutes();

    $app['auth']->viaRequest('/', function (\Ximdex\Runtime\WebRequest $request) {
        $valid = \Ximdex\Utils\Session::check(false);
        if( $valid ){
            $userId = \Ximdex\Utils\Session::get('userID');
            $user = new \Ximdex\Models\User($userId);
            if( !empty($user) ){
                \Ximdex\Utils\Session::refresh();
                return $user;
            }
        }


        return null;
    });

    $app->singleton(\Ximdex\Runtime\WebRequest::class, function () {
        return \Ximdex\Runtime\WebRequest::capture();
    });

    $webRequestHandler =  function(\Ximdex\Runtime\WebRequest $request){
        $valid = \Ximdex\Utils\Session::check(false);

        $actionRootName = "Action_";

        $request->setUsualParams();

        if (!$request->has('module')) {
            $actionPath = XIMDEX_ROOT_PATH .
                DIRECTORY_SEPARATOR . 'actions' .
                DIRECTORY_SEPARATOR . $request->input('action');
        } else {
            $path_module = ModulesManager::path($request->input('module'));
            $actionPath = sprintf('%s%s%s%s%s%s',
                XIMDEX_ROOT_PATH,
                $path_module,
                DIRECTORY_SEPARATOR,
                'actions',
                DIRECTORY_SEPARATOR,
                $request->input('action'));
        }

        /* @var $actionController \Ximdex\MVC\ActionAbstract */
        $actionController = \Ximdex\MVC\ActionFactory::getAction($request);

        if ($actionController == NULL) {
            return response('PAGE NOT FOUND', 404);
        } else {
            //$this->setUserState();
            //$stats = $this->actionStatsStart();
            $actionController->execute($request);
        }
    };

    $app->get('/', $webRequestHandler);
    $app->post('/', $webRequestHandler);

    try {
        $app->run();
    } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e){
        // Not found
        dump($e->getMessage());
        dump($e->getTraceAsString());
    } catch (\Illuminate\Auth\AuthenticationException $e){
        dump($e->getMessage());
        dump($e->getTraceAsString());
    }
}
