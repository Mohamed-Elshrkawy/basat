<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Amenity extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',
    ];

    protected $translatable = [
        'name',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== العلاقات ====================

    /**
     * العلاقة مع VehicleAmenities
     */
    public function vehicleAmenities(): HasMany
    {
        return $this->hasMany(VehicleAmenity::class);
    }

    /**
     * العلاقة مع السيارات (many-to-many)
     */
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'vehicle_amenities')
            ->withPivot('price')
            ->withTimestamps();
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ==================== Helper Methods ====================

    /**
     * عدد السيارات التي لديها هذه الوسيلة
     */
    public function getVehiclesCount(): int
    {
        return $this->vehicleAmenities()->count();
    }

    /**
     * متوسط سعر هذه الوسيلة عبر جميع السيارات
     */
    public function getAveragePrice(): float
    {
        return (float) $this->vehicleAmenities()->avg('price');
    }

    /**
     * الحصول على اسم الوسيلة (عربي)
     */
    public function getNameArAttribute(): string
    {
        return $this->getTranslation('name', 'ar');
    }

    /**
     * الحصول على اسم الوسيلة (إنجليزي)
     */
    public function getNameEnAttribute(): string
    {
        return $this->getTranslation('name', 'en');
    }

    /**
     * الحصول على الوصف (عربي)
     */
    public function getDescriptionArAttribute(): ?string
    {
        return $this->getTranslation('description', 'ar');
    }

    /**
     * الحصول على الوصف (إنجليزي)
     */
    public function getDescriptionEnAttribute(): ?string
    {
        return $this->getTranslation('description', 'en');
    }
}
