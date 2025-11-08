<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Route extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'start_point_name',
        'end_point_name',
        'range_km',
        'start_city_id',
        'end_city_id',
        'is_active'
    ];

    protected $translatable = [
        'name',
        'start_point_name',
        'end_point_name'
    ];

    protected $casts = [
        'range_km' => 'decimal:2',
        'is_active' => 'boolean',
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'return_arrival_time' => 'datetime',
        'return_departure_time' => 'datetime',
    ];

    // العلاقات
    public function startCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'start_city_id');
    }

    public function endCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'end_city_id');
    }

    public function stops(): BelongsToMany
    {
        return $this->belongsToMany(Stop::class, 'route_stops')
            ->withPivot(['order'])
            ->withTimestamps()
            ->orderBy('route_stops.order');
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('order');
    }

    // علاقة الرحلات
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
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

    public function scopeFromCity($query, $cityId)
    {
        return $query->where('start_city_id', $cityId);
    }

    public function scopeToCity($query, $cityId)
    {
        return $query->where('end_city_id', $cityId);
    }

    public function scopeBetweenCities($query, $startCityId, $endCityId)
    {
        return $query->where('start_city_id', $startCityId)
            ->where('end_city_id', $endCityId);
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

    public function getStopsCount(): int
    {
        return $this->stops()->count();
    }

    public function getSchedulesCount(): int
    {
        return $this->schedules()->count();
    }

    public function getActiveSchedulesCount(): int
    {
        return $this->schedules()->active()->count();
    }

    public function getFullRouteName(): string
    {
        return $this->name . ' (' .
            $this->startCity?->name . ' - ' .
            $this->endCity?->name . ')';
    }
}
