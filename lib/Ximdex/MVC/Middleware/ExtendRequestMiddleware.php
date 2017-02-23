<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 23/02/17
 * Time: 11:55
 */

namespace Ximdex\MVC\Middleware;

use Closure;
use Ximdex\Models\Action;
use Ximdex\Runtime\WebRequest;

class ExtendRequestMiddleware {
    public function handle(WebRequest $request, Closure $next) {

        $action = 'browser3';

        if (!$request->has('module') && $request->has('mod')){
            $request['module'] = $request->input('mod');
        }

        if (!$request->has('module') && $request->has('modsel')){
            $request['module'] = $request->input('modsel');
        }

        if (!$request->has('nodeid') && $request->has('nodes') && !empty($request->input('nodes', ''))){
            $request['nodeid'] = $request->input('nodes')[0];
        }

        $request['action'] = $request->input('action', $action);
        $request['method'] = $request->input('method', 'index');
        $request['module'] = $request->input('module', '');

        $request['out'] = 'WEB';

        if($request->has('nodeid')){
            $action = new Action();
            $action->setByCommandAndModule($request->input('action'), $request->input('nodeid'), $request->input('module'));
            if(!empty($action->get('IdAction'))) {
                $request['actionid'] = $action->get('IdAction');
            }
        }

        return $next($request);
    }
}