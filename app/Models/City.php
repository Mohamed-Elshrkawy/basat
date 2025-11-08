<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    /**
     * Count routes from/to this city
     */
    public function getRoutesCountAttribute(): int
    {
        return $this->routesAsStart()->count() + $this->routesAsEnd()->count();
    }

    public function routesAsStart(): HasMany
    {
        return $this->hasMany(PublicBusRoute::class, 'start_city_id');
    }

    public function routesAsEnd(): HasMany
    {
        return $this->hasMany(PublicBusRoute::class, 'end_city_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    public function getAllRelatedRoutes()
    {
        return PublicBusRoute::where(function ($query) {
            $query->where('start_city_id', $this->id)
                ->orWhere('end_city_id', $this->id);
        })->get();
    }


}
