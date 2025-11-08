<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomMediaPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        // المسار: model_name/collection_name/
        return $this->getBasePath($media) . '/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive/';
    }

    protected function getBasePath(Media $media): string
    {
        // استخراج اسم الموديل من model_type
        $modelName = strtolower(class_basename($media->model_type));

        return $modelName . '/' . $media->collection_name;
    }
}
