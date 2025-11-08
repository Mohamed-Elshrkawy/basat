<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;
        return response()->json([
            'status' => true,
            'code' => 'wallet_details_retrieved',
            'message' => __('messages.wallet_details_retrieved'),
            'data' => [
                'balance' => (float) $wallet->balance,
            ]
        ]);
    }

    public function transactions(Request $request)
    {
        $transactions = $request->user()->wallet->transactions()->latest()->paginate(20);
        return WalletTransactionResource::collection($transactions);
    }

    public function recharge(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
            'payment_method' => 'required|string|in:wallet,visa,cash',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $wallet = $user->wallet;
            $amount = $request->amount;

            // Update wallet balance
            $wallet->increment('balance', $amount);

            // Create transaction record
            $wallet->transactions()->create([
                'amount' => $amount,
                'type' => 'credit',
                'description' => 'Wallet Recharge',
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'code' => 'wallet_recharged_successfully',
                'message' => __('messages.wallet_recharged_successfully'),
                'data' => [
                    'new_balance' => (float) $wallet->fresh()->balance,
                    'recharged_amount' => (float) $amount,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => false,
                'code' => 'wallet_recharge_failed',
                'message' => __('messages.wallet_recharge_failed'),
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 