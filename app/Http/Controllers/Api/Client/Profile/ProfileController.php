<?php

namespace App\Http\Controllers\Api\Client\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Rider\UpdateProfileRequest;
use App\Http\Resources\Api\General\Account\ShowProfileResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private $user;

    public function __construct(Request $request)
    {
        $this->user = $request->user();
    }

    public function show(Request $request): \Illuminate\Http\JsonResponse
    {
        return json(ShowProfileResource::make($this->user));
    }

    public function updatePassword(ChangePasswordRequest $request): \Illuminate\Http\JsonResponse
    {
        if (!Hash::check($request->current_password, $this->user->password)) {
            return json(__('The provided current password does not match our records.'), status: 'fail', headerStatus: 422);
        }
        $this->user->update($request->only('password'));
        return json(ShowProfileResource::make($this->user->refresh()), __('Password was updated successfully'));
    }

//    public function sendOtp(EditAuthRequest $request): \Illuminate\Http\JsonResponse
//    {
//        $authService = new AuthService();
//
//        $otp = generateOtp();
//
//        $this->user->authVerifications()->updateOrCreate([
//            $request->auth_type => $request->auth
//        ], [
//            'reset_code' => $otp,
//            'phone_code' => $request->auth_type == 'phone' ? $request->phone_code : null
//        ]);
//
//        $authService->SendOtp($this->user, $request, $otp, 'update_' . $request->auth_type);
//
//        return json(__('OTP sent successfully'));
//    }
//
//    public function updateAuth(UpdateAuthRequest $request): \Illuminate\Http\JsonResponse
//    {
//        $this->user->update([
//            $request->auth_type => $request->auth,
//            'phone_code' => $request->auth_type == 'phone' ? $request->phone_code : $this->user->phone_code
//        ]);
//
//        $this->user->authVerifications()->where($request->auth_type, $request->auth)->delete();
//
//        return json(ShowProfileResource::make($this->user->refresh()), __('Auth was updated successfully'));
//    }

    public function update(UpdateProfileRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->user->update($request->validated());

        return json(ShowProfileResource::make($this->user->refresh()));
    }

    public function updateLocale(string $locale): \Illuminate\Http\JsonResponse
    {
        $this->user->update([
            'locale' => $locale
        ]);

        return json(ShowProfileResource::make($this->user->refresh()));
    }

    public function switchNotification(): \Illuminate\Http\JsonResponse
    {
        $this->user->update([
            'is_notify' => !$this->user->is_notify
        ]);
        return json(ShowProfileResource::make($this->user->refresh()));
    }

    public function deleteAccount(): \Illuminate\Http\JsonResponse
    {
        $this->user->delete();

        return json(__('Your account has been deleted successfully'));
    }
}
