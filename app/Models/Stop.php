<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Stop extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'is_active'
    ];

    // Spatie Translatable
    protected $translatable = ['name'];

    protected $casts = [
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'is_active' => 'boolean'
    ];

    // العلاقات
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'route_stops')
            ->withPivot(['arrival_time', 'departure_time', 'order'])
            ->withTimestamps()
            ->orderBy('route_stops.order');
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeNearby($query, $lat, $lng, $radiusKm = 10)
    {
        $degrees = $radiusKm / 111;

        return $query->whereBetween('lat', [$lat - $degrees, $lat + $degrees])
            ->whereBetween('lng', [$lng - $degrees, $lng + $degrees]);
    }

    // Helper Methods
    public function getNameArAttribute()
    {
        return $this->getTranslation('name', 'ar');
    }

    public function getNameEnAttribute()
    {
        return $this->getTranslation('name', 'en');
    }
}
