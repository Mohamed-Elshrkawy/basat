<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'brand_id',
        'vehicle_model_id',
        'plate_number',
        'seat_count',
        'type',
        'is_active',
    ];

    protected $casts = [
        'seat_count' => 'integer',
        'is_active' => 'boolean',
    ];

    // ==================== العلاقات ====================

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    /**
     * العلاقة مع VehicleAmenities (pivot)
     */
    public function vehicleAmenities(): HasMany
    {
        return $this->hasMany(VehicleAmenity::class);
    }

    /**
     * العلاقة مع Amenities مباشرة (many-to-many)
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'vehicle_amenities')
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublicBus($query)
    {
        return $query->where('type', 'public_bus');
    }

    public function scopePrivateBus($query)
    {
        return $query->where('type', 'private_bus');
    }

    public function scopeSchoolBus($query)
    {
        return $query->where('type', 'school_bus');
    }

    public function scopeWithSeatsGreaterThan($query, $seats)
    {
        return $query->where('seat_count', '>=', $seats);
    }

    // ==================== Helper Methods ====================

    /**
     * الحصول على الوسائل المجانية
     */
    public function getFreeAmenities()
    {
        return $this->vehicleAmenities()->free()->with('amenity')->get();
    }

    /**
     * الحصول على الوسائل المدفوعة
     */
    public function getPaidAmenities()
    {
        return $this->vehicleAmenities()->paid()->with('amenity')->get();
    }

    /**
     * إجمالي سعر الوسائل المدفوعة
     */
    public function getTotalAmenitiesPrice(): float
    {
        return (float) $this->vehicleAmenities()->sum('price');
    }

    /**
     * عدد الوسائل المتاحة
     */
    public function getAmenitiesCount(): int
    {
        return $this->vehicleAmenities()->count();
    }

    /**
     * هل السيارة لديها وسيلة معينة؟
     */
    public function hasAmenity($amenityId): bool
    {
        return $this->vehicleAmenities()
            ->where('amenity_id', $amenityId)
            ->exists();
    }

    /**
     * الحصول على معلومات الوسائل للعرض
     */
    public function getAmenitiesSummary(): array
    {
        $amenities = $this->vehicleAmenities()->with('amenity')->get();

        return [
            'total' => $amenities->count(),
            'free' => $amenities->where('price', 0)->count(),
            'paid' => $amenities->where('price', '>', 0)->count(),
            'total_price' => $amenities->sum('price'),
        ];
    }

    /**
     * نوع السيارة بالعربي
     */
    public function getTypeArabic(): string
    {
        return match($this->type) {
            'public_bus' => 'حافلة عامة',
            'private_bus' => 'حافلة خاصة',
            'school_bus' => 'حافلة مدرسية',
            default => $this->type,
        };
    }

    /**
     * معلومات كاملة عن السيارة
     */
    public function getFullInfo(): string
    {
        $brand = $this->brand?->name ?? '';
        $model = $this->vehicleModel?->name ?? '';
        $plate = $this->plate_number;

        return "{$brand} {$model} - {$plate}";
    }

    /**
     * حالة السيارة مع Badge
     */
    public function getStatusBadge(): array
    {
        if (!$this->is_active) {
            return ['label' => 'غير نشط', 'color' => 'danger'];
        }

        if (!$this->driver_id) {
            return ['label' => 'بدون سائق', 'color' => 'warning'];
        }

        return ['label' => 'نشط', 'color' => 'success'];
    }
}
