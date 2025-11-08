<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Rider\ChangePasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $validated = $request->validated();
        $userData = collect($validated)->except('avatar')->all();
        $user->update($userData);

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }
        return new UserResource($user->fresh());
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_current_password',
                'message' => __('messages.invalid_current_password'),
                'errors' => [
                    'current_password' => [__('messages.invalid_current_password')]
                ]
            ], 422);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json([
            'status' => true,
            'code' => 'password_changed_successfully',
            'message' => __('messages.password_changed_successfully')
        ]);
    }
} 