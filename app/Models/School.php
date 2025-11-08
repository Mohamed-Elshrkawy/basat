<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'is_active' => 'boolean',
    ];

    public function packages()
    {
        // Assuming a school can offer multiple packages
        return $this->belongsToMany(SchoolPackage::class, 'school_package_school');
    }
}
