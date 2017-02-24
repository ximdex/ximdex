<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 22/02/17
 * Time: 18:08
 */

namespace Ximdex\MVC;

use Laravel\Lumen\Application;
use Ximdex\Runtime\WebRequest;

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

        $app->alias('Ximdex\Runtime\WebRequest', 'request');

        // Auth middleware
        $app->routeMiddleware([
            'webauth' => \Ximdex\MVC\Middleware\WebAuthMiddleware::class,
            'extend' => \Ximdex\MVC\Middleware\ExtendRequestMiddleware::class,
            'actionauth' => \Ximdex\MVC\Middleware\ActionAuthMiddleware::class,
        ]);
    }
}