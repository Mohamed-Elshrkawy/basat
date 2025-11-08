<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\UpdateFcmTokenRequest;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmController extends Controller
{
    /**
     * Update driver's FCM token
     *
     * @param UpdateFcmTokenRequest $request
     * @return JsonResponse
     */
    public function updateToken(UpdateFcmTokenRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => __('messages.driver_not_found'),
            ], 404);
        }

        $token = $request->validated()['fcm_token'];

        // Store token on user (single current device token)
        $user = $request->user();
        $user->fcm_token = $token;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => __('messages.fcm_token_updated_successfully'),
            'data' => [
                'fcm_token' => $user->fcm_token,
            ],
        ]);
    }

    /**
     * Remove driver's FCM token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => __('messages.driver_not_found'),
            ], 404);
        }

        $token = $request->fcm_token;

        // Clear user's token if matches
        $user = $request->user();
        if ($user->fcm_token === $token) {
            $user->fcm_token = null;
            $user->save();
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.fcm_token_removed_successfully'),
            'data' => [
                'fcm_token' => $user->fcm_token,
            ],
        ]);
    }

    /**
     * Get driver's FCM tokens
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTokens(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => __('messages.driver_not_found'),
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.fcm_tokens_retrieved_successfully'),
            'data' => [
                'fcm_token' => $request->user()->fcm_token,
            ],
        ]);
    }
}
