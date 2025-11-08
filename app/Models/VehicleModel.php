<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'default_seat_count',
        'is_active',
    ];

    protected $casts = [
        'default_seat_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the brand that owns the vehicle model
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get all vehicles using this model
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vehicle_model_id');
    }

    /**
     * Scope for active vehicle models only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
