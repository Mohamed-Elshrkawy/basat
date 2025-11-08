<?php

namespace App\Traits;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Exception;

trait HasWallet
{
    public function wallet(): MorphOne
    {
        return $this->morphOne(Wallet::class, 'payable');
    }

    public function getOrCreateWallet()
    {
        if (!$this->wallet) {
            return $this->wallet()->create();
        }
        return $this->wallet;
    }

    public function balance(): float
    {
        return (float) optional($this->wallet)->balance ?? 0;
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->getOrCreateWallet()->transactions();
    }

    public function deposit(float $amount, array $description = null, array $meta = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $meta) {
            $wallet = $this->getOrCreateWallet();
            $wallet->increment('balance', $amount);

            return $wallet->transactions()->create([
                'type' => 'deposit',
                'status' => 'completed',
                'amount' => $amount,
                'description' => $description,
                'meta' => $meta,
                'is_up' => true
            ]);
        });
    }

    public function withdraw(float $amount, array $description = null, array $meta = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $description, $meta) {
            $wallet = $this->getOrCreateWallet();

            if ($wallet->balance < $amount) {
                throw new Exception('Insufficient balance.');
            }

            $wallet->decrement('balance', $amount);

            return $wallet->transactions()->create([
                'type' => 'withdraw',
                'status' => 'completed',
                'amount' => $amount,
                'description' => $description,
                'meta' => $meta,
                'is_up' => false
            ]);
        });
    }
}
