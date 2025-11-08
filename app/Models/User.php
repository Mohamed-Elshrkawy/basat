<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia;


    protected $guarded = ['id'];


    protected $hidden = [
        'password',
        'remember_token',
        'reset_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function device(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Device::class, 'user_id');
    }


    public function driver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Driver::class, 'user_id');
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }


    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }


    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'parent_id');
    }

    public function tripsAsRider(): HasMany
    {
        return $this->hasMany(Trip::class, 'rider_id');
    }

    public function tripsAsDriver(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }


    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'driver_id');
    }


    public function ratingsGiven(): HasMany
    {
        return $this->hasMany(Rating::class, 'rater_id');
    }

    public function ratingsReceived(): HasMany
    {
        return $this->hasMany(Rating::class, 'rated_id');
    }

    public function problemReports(): HasMany
    {
        return $this->hasMany(ProblemReport::class, 'reporter_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->hasMedia('avatar') ? $this->getFirstMediaUrl('avatar') : null;
    }
}
