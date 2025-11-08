<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class SchoolPackage extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description'];

    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'price' => 'float',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
    ];
}
