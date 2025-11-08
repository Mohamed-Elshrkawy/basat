<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\General\Auth\ForgetPasswordRequest;
use App\Http\Requests\Api\General\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\General\Auth\VerifyForgetPasswordRequest;
use App\Services\General\AuthService;


class PasswordController extends Controller
{
    public function __construct(private readonly AuthService $service){}

    public function forget(ForgetPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->forget($request);
    }

    public function verify(VerifyForgetPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        return json(__('Verified successfully'));
    }

    public function reset(ResetPasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->reset($request);
    }
}
