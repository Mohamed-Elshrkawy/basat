<?php

namespace App\Http\Middleware;

use App\Enums\UserTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserTypeMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->header('user_type')) {
            return json(__('User type not found, please add user-type header'), status: 'fail', headerStatus: 422);
        }elseif(!in_array($request->header('user_type'), UserTypeEnum::toArray())) {
            return json(__('this user type not found'), status: 'fail', headerStatus: 422);
        }
        return $next($request);
    }
}
