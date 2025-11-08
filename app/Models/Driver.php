<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Driver extends Model
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $casts = [
        'avg_rating' => 'float',
        'current_lat' => 'decimal:8',
        'current_lng' => 'decimal:8',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
