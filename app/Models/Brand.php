<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all vehicle models for this brand
     */
    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class, 'brand_id');
    }

    /**
     * Get all vehicles for this brand
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'brand_id');
    }

    /**
     * Scope for active brands only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
