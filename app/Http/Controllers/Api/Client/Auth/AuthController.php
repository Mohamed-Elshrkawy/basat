<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\General\Auth\LoginRequest;
use App\Http\Requests\Api\General\Auth\LogoutRequest;
use App\Http\Requests\Api\General\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\General\Auth\RegisterRequest;
use App\Http\Requests\Api\General\Auth\ResendOtpRequest;
use App\Http\Requests\Api\General\Auth\VerifyRequest;
use App\Services\General\AuthService;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service){}

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->login($request);
    }

    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->register($request);
    }

    public function verify(VerifyRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->verify($request);
    }

    public function resendOtp(ResendOtpRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->service->getUser($request);

        if (is_null($user)) {
            return json(__('Account doest match or record'), status: 'fail', headerStatus: 422);
        }

        $otp = generateOtp();

        $user->update([
            'reset_code' => $otp
        ]);

        $this->service->sendOtp($user, $request, $otp, 'verify');

        return json(__('OTP sent successfully'));
    }

    public function logout(LogoutRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->logout($request);
    }

    public function refreshToken(RefreshTokenRequest $request): \Illuminate\Http\JsonResponse
    {
        return $this->service->refreshToken($request);
    }
}
