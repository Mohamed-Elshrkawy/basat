<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\VerifyMobileRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $verification_code = $this->sendVerificationCode($data['phone']);
        $user = User::create([
            'name' => $data['name'],
            'national_id' => $data['national_id'],
            'user_type' => 'client',
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'verification_code' => $verification_code,
        ]);

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return response()->json([
            'status' => false,
            'code' => 'phone_verification_required',
            'message' => __('code sent successfully, please verify your phone number'),
            'data' => [
                'verification_code' => $verification_code
            ]
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('phone', $data['phone'])->where('user_type', $data['user_type'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_credentials',
                'message' => __('auth.failed'),
            ], 401);
        }

        if (!$user->mobile_verified_at) {
            return response()->json([
                'status' => false,
                'code' => 'phone_verification_required',
                'message' => __('auth.unverified_phone'),
            ], 403);
        }
        // Persist FCM token if provided in login request (support both fcm_token and device_token)
        $incomingToken = $data['fcm_token'] ?? ($data['device_token'] ?? null);
        if (!empty($incomingToken)) {
            $user->fcm_token = $incomingToken;
            $user->save();
        }

        $token = $user->createToken('auth-token-' . $data['user_type'])->plainTextToken;
        return response()->json([
            'status' => true,
            'code' => 'logged_in_successfully',
            'message' => __('auth.logged_in_successfully'),
            'data' => [
                'user' => new UserResource($user),
                'token' => $token
            ]
        ]);
    }

    public function verifyMobile(VerifyMobileRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('phone', $data['phone'])->where('user_type', 'client')->firstOrFail();
        if ($user->phone_verified_at) {
            return response()->json([
                'status' => false,
                'code' => 'already_verified',
                'message' => __('auth.already_verified'),
            ], 422);
        }
        if ($user->verification_code !== $data['verification_code']) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_verification_code',
                'message' => __('auth.invalid_verification_code'),
            ], 422);
        }
        $user->mobile_verified_at = now();
        $user->verification_code = null;
        $user->save();
        return response()->json([
            'status' => true,
            'code' => 'phone_verified_successfully',
            'message' => __('auth.phone_verified_successfully'),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->validated()['phone'])->firstOrFail();
        $verification_code = $this->sendVerificationCode($user->phone);
        return response()->json([
            'status' => true,
            'code' => 'password_reset_code_sent',
            'message' => __('passwords.sent'),
            'data' => [
                'verification_code' => $verification_code
            ]
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('phone', $data['phone'])->firstOrFail();
        if ($user->verification_code !== $data['verification_code']) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_verification_code',
                'message' => __('auth.invalid_verification_code'),
            ], 422);
        }
        $user->password = Hash::make($data['password']);
        $user->verification_code = null;
        $user->save();
        return response()->json([
            'status' => true,
            'code' => 'password_reset_successfully',
            'message' => __('passwords.reset'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'code' => 'logged_out_successfully',
            'message' => __('auth.logged_out_successfully'),
            'data' => null
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        // Validate input password
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verify provided password matches user's current password
        if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_password',
                'message' => __('messages.invalid_current_password'),
                'data' => null,
            ], 422);
        }

        $user->status = 'deleted_request';
        $user->save();
        $user->tokens()->delete();
        return response()->json([
            'status' => true,
            'code' => 'account_deletion_requested',
            'message' => __('messages.account_deletion_requested'),
            'data' => null
        ]);
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|exists:users,phone',
        ]);
        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'code' => 'phone_not_found',
                'message' => __('auth.phone_not_found'),
                'data' => null
            ], 404);
        }
        if ($user->phone_verified_at) {
            return response()->json([
                'status' => false,
                'code' => 'already_verified',
                'message' => __('auth.already_verified'),
                'data' => null
            ]);
        }
        $verification_code = $this->sendVerificationCode($user->phone);
        return response()->json([
            'status' => true,
            'code' => 'verification_code_resent',
            'message' => __('auth.verification_code_resent'),
            'data' => [
                'verification_code' => $verification_code
            ]
        ]);
    }

    public function sendVerificationCode($phone)
    {
        $code = 1111;
        $user = User::where('phone', $phone)->first();
        if ($user) {
            $user->verification_code = $code;
            $user->save();
        }
        return $code;
    }

    /**
     * Update the device token for the authenticated user.
     */
    public function updateDeviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => 'required|string',
        ]);
        $request->user()->update([
            'device_token' => $validated['device_token']
        ]);
        return response()->json([
            'status' => true,
            'code' => 'device_token_updated',
            'message' => 'Device token updated successfully.',
            'data' => null
        ]);
    }

    /**
     * Verify the reset code only.
     */
    public function verifyResetCode(\App\Http\Requests\Api\V1\Auth\VerifyMobileRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $user = \App\Models\User::where('phone', $data['phone'])->firstOrFail();
        if ($user->verification_code !== $data['verification_code']) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_verification_code',
                'message' => __('auth.invalid_verification_code'),
            ], 422);
        }
        return response()->json([
            'status' => true,
            'code' => 'reset_code_verified',
            'message' => __('messages.reset_code_verified'),
        ]);
    }
}
