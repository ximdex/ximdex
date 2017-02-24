<?php

namespace Ximdex\API\Middleware;

use Illuminate\Http\Request;
use Closure;
use Ximdex\API\APIException;
use Ximdex\API\APIResponse;
use Ximdex\Models\User;

class AuthReadMiddleware {
    public function handle ( Request $request, Closure $next ){

        $user = new User($request->input('userID'));
        $hasPermission = $user->hasPermission('ApiRead');

        if($hasPermission){
            return $next($request);
        }
        $response = new APIResponse();
        return $response->setStatus(APIResponse::ERROR)->setMessage("You aren't allowed to perform this action.");

    }
}