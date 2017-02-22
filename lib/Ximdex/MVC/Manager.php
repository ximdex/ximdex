<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 22/02/17
 * Time: 18:08
 */

namespace Ximdex\MVC;


use Laravel\Lumen\Application;

class Manager {
    public static function setupApp(){
        $app = new Application();

        // Setting logget to Ximdex logger
        $app->configureMonologUsing(function($monolog) {
            $monolog->pushHandler(\Ximdex\Logger::get()->getHandlers()[0]);
            return $monolog;
        });

        // WebRequest is a wrapper of Request
        $app->singleton(\Ximdex\Runtime\WebRequest::class, function () {
            return \Ximdex\Runtime\WebRequest::capture();
        });

        // Auth middleware
        $app->routeMiddleware([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        ]);
    }
}