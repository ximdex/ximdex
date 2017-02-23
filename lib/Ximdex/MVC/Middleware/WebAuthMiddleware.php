<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 23/02/17
 * Time: 10:39
 */

namespace Ximdex\MVC\Middleware;

use Closure;
use Ximdex\Runtime\WebRequest;

class WebAuthMiddleware {
    public function handle(WebRequest $request, Closure $next) {

        $valid = \Ximdex\Utils\Session::check(false);
        if( $valid ){
            $userId = \Ximdex\Utils\Session::get('userID');
            $user = new \Ximdex\Models\User($userId);
            if( !empty($user) ){
                \Ximdex\Utils\Session::refresh();
                $request['userID'] = $user->getID();
                $request['userLogged'] = $user->getLogin();
            }
        }

        return $next( $request );
    }
}