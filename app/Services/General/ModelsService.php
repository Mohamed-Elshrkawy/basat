<?php

namespace App\Services\General;

use Illuminate\Support\Facades\File;

class ModelsService
{

    public static function paths()
    {
        return array_column(self::models(), 'model');
    }

    public static function models(): array
    {
        $modelsPath = app_path('Models');

        if (!File::isDirectory($modelsPath)) {
            return [];
        }

        $files = File::files($modelsPath);

        $modelNames = [];

        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);
            if ($fileName == 'AppMedia' || str_contains($fileName, 'Translation')) {
                continue;
            }
            $module = preg_replace('/(?<!^)([A-Z])/', ' $1', $fileName);
            $modelName = str($fileName)->studly()->value();
            $modelName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $modelName));
            $modelNames[] = ['model' => str($modelName)->studly()->value(), 'title' => $module];
        }

        return $modelNames;
    }

    public static function modules()
    {
        return array_column(self::models(), 'title');
    }

}
