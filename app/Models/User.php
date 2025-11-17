<?php

namespace App\Models;

use App\Traits\HasWallet;
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
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia, HasWallet;


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

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'user_id');
    }


    public function driver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Driver::class, 'user_id');
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }

    public function cities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_driver', 'driver_id', 'city_id')
            ->withTimestamps();
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
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

    public function privateTrips(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id')->where('type', 'private_bus');
    }

    public function privateTripsAsDriver(): HasMany
    {
        return $this->hasMany(Booking::class, 'driver_id')->where('type', 'private_bus');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->hasMedia('avatar') ?
            $this->getFirstMediaUrl('avatar')
            : $this->getDefaultAvatarUrl();
    }

    public function getDefaultAvatarUrl(): string
    {
        $name = $this->name ?? 'User';
        $words = explode(' ', trim($name));

        if ($this->isArabic($name)) {
            $displayName = implode('+', array_slice($words, 0, 2));
        } else {
            if (count($words) >= 2) {
                $displayName = mb_substr($words[0], 0, 1) . '+' . mb_substr($words[1], 0, 1);
            } else {
                $displayName = mb_substr($words[0], 0, 2);
            }
        }

        return sprintf(
            'https://ui-avatars.com/api/?name=%s&color=7F9CF5&background=EBF4FF&size=300&bold=true&format=svg&font-size=0.33',
            urlencode($displayName)
        );
    }

    public function isArabic(string $name): bool
    {
        return preg_match('/[\p{Arabic}]/u', $name) === 1;
    }
}
