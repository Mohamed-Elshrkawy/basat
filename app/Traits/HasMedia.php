<?php

namespace App\Traits;

use App\Models\Media;
use App\Observers\GeneralObserver;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    public static function bootHasMedia()
    {
        static::observe(GeneralObserver::class);
    }

    public function getMediaColumns(): array
    {
        return $this->mediaColumns ?? [];
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function __get($key)
    {
        if (isset($this->mediaColumns[$key])) {
            $isSingle = $this->mediaColumns[$key] === 0;

            $query = $this->media()->where('option', $key)->latest();

            if ($isSingle) {
                $media = $query->first();

                return $media ? [
                    'id'     => $media->id,
                    'path'   => asset('storage/' . trim($media->path, '/') . '/' . $media->name),
                    'type'   => $media->type,
                    'option' => $media->option,
                ] : null;
            }

            return $query->get()->map(function ($media) {
                return [
                    'id'     => $media->id,
                    'path'   => asset('storage/' . trim($media->path, '/') . '/' . $media->name),
                    'type'   => $media->type,
                    'option' => $media->option,
                ];
            });
        }

        return parent::__get($key);
    }
}
