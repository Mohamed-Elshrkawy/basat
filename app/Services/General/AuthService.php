<?php

namespace App\Services\General;

use App\Http\Resources\Api\General\Account\ShowProfileResource;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthService
{
    public function login($request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = $this->getUser($request);

            if (is_null($user)) {
                return json(__('Account doest match or record'), status: 'fail', headerStatus: 422);
            }

            if (!Hash::check($request->password, $user->password)) {
                return json(__('Invalid credentials'), status: 'fail', headerStatus: 422);
            }

            if (!$user->is_active) {

                $otp = generateOtp();

                $user->update([
                    'reset_code' => $otp
                ]);

                $this->SendOtp($user, $request, $otp, 'verify');

                return json(ShowProfileResource::make($user),__('Please verify your account'));
            }

            if ($user->is_ban) {
                return json(__('Account banned or blocked, please contact with admin'), status: 'fail', headerStatus: 401);
            }

            if($request->device_token && $request->type){
                $this->createDevice($request, $user);
            }


            DB::commit();

            $token = $user->createToken('auth-token-' . request()->header('user_type'))->plainTextToken;


            data_set($user, 'token', $token);

            return json(ShowProfileResource::make($user), __('Logged in successfully'));

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }
    }

    public function register($request): \Illuminate\Http\JsonResponse
    {
        DB::beginTransaction();

        try {
            $user_type = request()->header('user_type');

            $otp = generateOtp();

            $request->merge([
                'reset_code' => $otp,
                'user_type' => $user_type,
            ]);



            $user = User::query()->updateOrCreate([
                'email' => $request->email,
                'user_type' => $user_type,
                'phone' => $request->phone
            ], $request->all());


            $this->sendOtp($user, $request, $otp, 'verify');

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }

        return json(__('Account created successfully, code was sent'));
    }

    public function SendOtp($user, $request, $otp = null, $type = null): void
    {

        //TODO: Send OTP to user
//        if ($user->email) {
//            if ($type == 'verify') {
//                Mail::to($user->email)->send(new \App\Mail\VerifyNewUser($user));
//            } elseif ($type == 'update_password') {
//                Mail::to($user->email)->send(new \App\Mail\ForgetPassword($user));
//            } elseif ($type == 'update_email') {
//                Mail::to($request->auth)->send(new \App\Mail\UpdateEmail($user, $otp));
//            }
//        }
    }

    public function verify($request): \Illuminate\Http\JsonResponse
    {
        $user = $this->getUser($request);

        try {

            DB::beginTransaction();

            if ($user->reset_code != $request->code) {

                return json(__('OTP Code is wrong'), status: 'fail', headerStatus: 422);
            }

            $user->update([
                'reset_code' => null,
                'is_active' => true,
            ]);

            if ($user->is_ban) {
                return json(__('Account banned or blocked, please contact with admin'), status: 'fail', headerStatus: 401);
            }

            if ($request->device_token && $request->type) {
                $this->createDevice($request, $user);
            }

            $token = $user->createToken('auth-token-' . request()->header('user_type'))->plainTextToken;

            data_set($user, 'token', $token);

            DB::commit();

            return json(ShowProfileResource::make($user), __('Your account has been activated successfully'));

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }
    }

    public function logout($request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $agent_token = $request->header('agent_token');

            $user->devices()
                ->where(function ($query) use ($agent_token, $request) {
                    $query->where('agent_token', $agent_token)
                        ->orWhere('device_token', $request->device_token);
                })
                ->update(['status' => false]);

            if ($user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }

        } catch (\Exception $exception) {
            Log::error($exception);
        }

        return json( __('User Logged out successfully') );
    }


    public function refreshToken($request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $user->devices()
                ?->updateOrCreate(
                    $request->only('type') + ['device_token' => $request->old_device_token],
                    $request->only('type', 'device_token')
                );

        } catch (\Exception $exception) {
            Log::error($exception);
        }

        return json(
            __('Token was Refreshed successfully')
        );
    }


    public function forget($request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $this->getUser($request);

            $otp = generateOtp();

            $user->update([
                'reset_code' => $otp
            ]);

            $this->sendOtp($user, $request, $otp, 'update_password');

            return json(__('Code has been sent to you.'));
        } catch (\Exception $exception) {
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }
    }

    public function reset($request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = $this->getUser($request);

            $user->update([
                'password' => $request->password,
                'reset_code' => null
            ]);

            return json(__('Password updated successfully'));

        } catch (\Exception $exception) {
            Log::error($exception);
            return json(__('Server error'), status: 'fail', headerStatus: 500);
        }
    }

    public function getUser($request): ?User
    {
        $user_type = request()->header('user_type');

        return User::where('phone', $request->phone)
            ->where('user_type', $user_type)
            ->first();

    }

    private function createDevice($request, $user): void
    {
        try {
            $agent_token = $request->header('agent_token');
            Device::updateOrCreate(
                [
                    'agent_token' => $agent_token,
                    'user_id' => $user->id
                ],
                [
                    'device_token' => $request->device_token,
                    'type' => $request->type,
                    'status' => true
                ]
            );

        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }

}
