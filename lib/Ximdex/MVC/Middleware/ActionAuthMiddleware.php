<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 23/02/17
 * Time: 13:00
 */

namespace Ximdex\MVC\Middleware;

use Closure;
use Ximdex\Models\Action;
use Ximdex\Models\User;
use Ximdex\Runtime\WebRequest;

class ActionAuthMiddleware {
    public function handle(WebRequest $request, Closure $next) {
        $logged = !empty($request->input('userID'));

        if( !$logged ){
            $request['actionid'] = "";
            $request['action'] = 'login';
            $request['module'] = '';
            return $next($request);
        }

        $allwaysAllowedActions = Action::getAlwaysAllowedActions();

        if ( in_array( $request->input('action'), $allwaysAllowedActions, true ) ){
            return $next($request);
        }

        $nodes = $request->input('nodes', '');
        if ( empty($nodes) ){
            $nodes = [$request->input('nodeid')];
        }

        $userID = $request->input('userID');
        $user = new User($userID);
        $allowedActions = $user->getActionsOnNodeList($nodes);

        if ( $request->has('actionid') && in_array( $request->input('actionid', ''), $allowedActions, true ) ){
            return $next($request);
        }
        return response('Forbidden access for unknowed user', 403);
    }
}