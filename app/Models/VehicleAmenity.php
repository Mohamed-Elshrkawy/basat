<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAmenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'amenity_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ==================== العلاقات ====================

    /**
     * العلاقة مع السيارة
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * العلاقة مع الوسيلة
     */
    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }

    // ==================== Scopes ====================

    /**
     * الوسائل المجانية
     */
    public function scopeFree($query)
    {
        return $query->where('price', 0);
    }

    /**
     * الوسائل المدفوعة
     */
    public function scopePaid($query)
    {
        return $query->where('price', '>', 0);
    }

    /**
     * الوسائل لسيارة معينة
     */
    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    // ==================== Helper Methods ====================

    /**
     * هل الوسيلة مجانية؟
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * هل الوسيلة مدفوعة؟
     */
    public function isPaid(): bool
    {
        return $this->price > 0;
    }

    /**
     * الحصول على اسم الوسيلة (عربي)
     */
    public function getAmenityName(): string
    {
        return $this->amenity?->name ?? '';
    }

    /**
     * الحصول على أيقونة الوسيلة
     */
    public function getAmenityIcon(): ?string
    {
        return $this->amenity?->icon ?? null;
    }

    /**
     * تنسيق السعر للعرض
     */
    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return 'مجاني';
        }

        return number_format($this->price, 2) . ' ر.س';
    }

    /**
     * الحصول على badge للسعر
     */
    public function getPriceBadge(): array
    {
        if ($this->isFree()) {
            return [
                'label' => 'مجاني',
                'color' => 'success'
            ];
        }

        return [
            'label' => $this->getFormattedPrice(),
            'color' => 'warning'
        ];
    }

    /**
     * معلومات الوسيلة الكاملة
     */
    public function getFullInfo(): string
    {
        $name = $this->getAmenityName();
        $price = $this->getFormattedPrice();

        return "{$name} - {$price}";
    }

    // ==================== Accessors ====================

    /**
     * Accessor لاسم الوسيلة
     */
    public function getNameAttribute(): string
    {
        return $this->getAmenityName();
    }

    /**
     * Accessor للسعر المنسق
     */
    public function getPriceFormattedAttribute(): string
    {
        return $this->getFormattedPrice();
    }

    // ==================== Mutators ====================

    /**
     * Mutator للتأكد من أن السعر لا يكون سالب
     */
    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = max(0, $value);
    }
}
