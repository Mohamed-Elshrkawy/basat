<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Wallet extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function payable(): Relation
    {
        return $this->morphTo();
    }
    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }
}
