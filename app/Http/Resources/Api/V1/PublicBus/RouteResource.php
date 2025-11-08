<?php

namespace App\Http\Resources\Api\V1\PublicBus;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'start_point_name' => $this->getTranslation('start_point_name', app()->getLocale()),
            'end_point_name' => $this->getTranslation('end_point_name', app()->getLocale()),
        ];
    }
} 