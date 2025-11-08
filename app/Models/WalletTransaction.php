<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class WalletTransaction extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['description'];

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'description' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function related()
    {
        return $this->morphTo();
    }
}
