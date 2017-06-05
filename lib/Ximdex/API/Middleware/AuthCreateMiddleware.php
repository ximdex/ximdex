<?php

namespace Ximdex\API\Middleware;

use Illuminate\Http\Request;
use Closure;
use Ximdex\API\APIResponse;
use Ximdex\Models\User;

class AuthCreateMiddleware {
    public function handle ( Request $request, Closure $next ){

        $user = new User($request->input('userID'));
        $hasPermission = $user->hasPermission('ApiCreate');

        if($hasPermission){
            return $next($request);
        }
        $response = new APIResponse();
        return $response->setStatus(APIResponse::ERROR)->setMessage("You aren't allowed to perform this action.");

    }
}