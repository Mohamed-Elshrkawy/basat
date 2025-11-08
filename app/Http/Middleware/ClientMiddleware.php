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
        $user = $request->user();

        if (!$user) {
            $user->currentAccessToken()->delete();
            return json(__('User not authorized'), status: 'fail', headerStatus: 401);
        }

        if ($user->user_type != UserTypeEnum::Customer->value) {
            return json(__('User not authorized'), status: 'fail', headerStatus: 401);
        }

        if (!$user->is_active) {
            $user->currentAccessToken()->delete();
            return json(__('Account not activated'), status: 'fail', headerStatus: 401);
        }


        return $next($request);
    }
}
