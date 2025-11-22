<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class School extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'departure_time' => 'datetime:H:i',
        'return_time' => 'datetime:H:i',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * علاقة الباقات المتاحة للمدرسة
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(SchoolPackage::class, 'school_package_school');
    }

    /**
     * علاقة السائقين المسؤولين عن المدرسة
     */
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'driver_school', 'school_id', 'driver_id')
            ->where('user_type', 'driver')
            ->where('is_active', true)
            ->whereHas('vehicle' , function ($query) {
                $query->where('type', 'school_bus');
            })
            ->withTimestamps();
    }
}
