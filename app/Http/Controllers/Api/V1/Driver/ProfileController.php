<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Rider\ChangePasswordRequest;
use App\Http\Resources\Api\V1\Driver\DriverProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): DriverProfileResource
    {
        $user = $request->user()->load('driverProfile');
        return new DriverProfileResource($user);
    }

    public function update(UpdateProfileRequest $request): DriverProfileResource
    {
        $user = $request->user();
        $validated = $request->validated();
        DB::transaction(function () use ($request, $user, $validated) {
            $user->update([
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'national_id' => $validated['national_id'] ?? $user->national_id,
            ]);
            if (isset($validated['bio'])) {
                $user->driverProfile()->update([
                    'bio' => $validated['bio'],
                ]);
            }
            if ($request->hasFile('avatar')) {
                $user->clearMediaCollection('avatar');
                $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
            }
        });
        return new DriverProfileResource($user->fresh()->load('driverProfile'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_current_password',
                'message' => 'The provided current password does not match our records.',
            ], 422);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json([
            'status' => true,
            'code' => 'password_changed_successfully',
            'message' => 'Password changed successfully.'
        ]);
    }
} 