<?php

namespace App\Http\Middleware;

use App\Enums\UserTypeEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            auth('api')->logout(true);
            return json(__('User not authorized'), status: 'fail', headerStatus: 401);
        }

        if ($user->user_type != UserTypeEnum::Client->value) {
            return json(__('User not authorized'), status: 'fail', headerStatus: 401);
        }

        if (!$user->is_active) {
            auth('api')->logout(true);
            return json(__('Account not activated'), status: 'fail', headerStatus: 401);
        }

        if ($user->is_ban) {
            auth('api')->logout(true);
            return json(__('Account banned, please contact support'), status: 'fail', headerStatus: 422);
        }


        $request->merge(['client' => $user]);

        return $next($request);
    }
}
