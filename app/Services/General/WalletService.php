<?php

namespace App\Services\General;

use App\Http\Resources\Api\General\Wallet\WalletTransactionResource;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletService
{
    private $user;
    public function __construct()
    {
        $this->user = $this->getUser();
    }

    public function index($request): \Illuminate\Http\JsonResponse
    {
        $user = $this->user;

        if (!method_exists($user, 'getOrCreateWallet')) {
            return json(__('This model does not support wallets.'), status: 'fail', headerStatus: 400);
        }

        $wallet = $user->getOrCreateWallet();

        return json([
            'balance' => $wallet->balance,
            'transactions' => WalletTransactionResource::collection($wallet->transactions()->latest('id')->take($request->get('per_page', 10))->get()),
        ]);
    }

    public function transactions($request): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $user = $this->user;

        if (!method_exists($user, 'getOrCreateWallet')) {
            return json(__('This model does not support wallets.'), status: 'fail', headerStatus: 400);
        }

        $wallet = $user->getOrCreateWallet();
        $transactions = $wallet->transactions()
            ->latest()
            ->paginate($request->get('per_page', 10));

        return WalletTransactionResource::collection($transactions)->additional([
            'status' => 'success',
            'message' => $transactions->count() ? __('Transactions retrieved successfully'): __('No transactions found'),
        ]);
    }

    public function withdrawRequest($request): \Illuminate\Http\JsonResponse
    {
        $user = $this->user;

        if (!method_exists($user, 'getOrCreateWallet')) {
            return json(__('This model does not support wallets.'), status: 'fail', headerStatus: 400);
        }

        try {
            return DB::transaction(function () use ($user, $request) {
                $wallet = $user->getOrCreateWallet();

                if ($wallet->balance < $request->amount) {
                    return json(__('Insufficient balance.'), status: 'fail', headerStatus: 422);
                }

                $transaction = $wallet->transactions()->create([
                    'type' => 'withdraw_request',
                    'status' => 'pending',
                    'amount' => $request->amount,
                    'description' => [
                        'en' => "Withdraw request for {$request->amount} SAR",
                        'ar' => "طلب سحب {$request->amount} ريال سعودي"
                    ],
                    'is_up' => false
                ]);

                $transaction->withdrawRequest()->create([
                    'wallet_id' => $wallet->id,
                    'amount' => $request->amount,
                    'bank_name' => $request->bank_name,
                    'account_name' => $request->account_name,
                    'account_number' => $request->account_number,
                    'IBAN' => $request->IBAN,
                    'status' => 'pending',
                ]);

                $wallet->decrement('balance', $request->amount);
                $wallet->increment('withdrawal_balance', $request->amount);

                return json([
                    'balance' => $wallet->balance,
                    'transaction' => WalletTransactionResource::make($transaction->fresh()),
                ], __('Withdraw request submitted successfully'));
            });
        } catch (Exception $e) {
            Log::error('Withdraw request failed', ['error' => $e->getMessage()]);
            return json(__('Something went wrong. Please try again.'), status: 'fail', headerStatus: 500);
        }
    }

    public function chargeWallet($request): \Illuminate\Http\JsonResponse
    {
        $user = $this->user;

        if (!method_exists($user, 'getOrCreateWallet')) {
            return json(__('This model does not support wallets.'), status: 'fail', headerStatus: 400);
        }

        try {
            return DB::transaction(function () use ($user, $request) {
                $wallet = $user->getOrCreateWallet();

                $transaction = $wallet->transactions()->create([
                    'type' => 'charge',
                    'status' => 'success',
                    'amount' => $request->amount,
                    'description' => [
                        'en' => "Charge for {$request->amount} SAR",
                        'ar' => "شحن {$request->amount} ريال سعودي"
                    ],
                    'is_up' => true
                ]);

                $wallet->increment('balance', $request->amount);

                return json([
                    'balance' => $wallet->balance,
                    'transaction' => WalletTransactionResource::make($transaction->fresh()),
                ], __('Charge successful'));
            });
        } catch (Exception $e) {
            Log::error('Charge wallet failed', ['error' => $e->getMessage()]);
            return json(__('Something went wrong. Please try again.'), status: 'fail', headerStatus: 500);
        }
    }

    private function getUser()
    {
        $user= request()->user();

        return $user;

    }
}
