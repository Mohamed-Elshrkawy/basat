<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    public $translatable = ['title', 'content'];

    protected $guarded = ['id'];

    protected $casts = [
        'title' => 'array',
        'content' => 'array',
    ];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('image')
        );
    }


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
            ->useFallbackUrl(asset('images/placeholder-static-page.png'))
            ->useFallbackPath(public_path('images/placeholder-static-page.png'));
    }
}
