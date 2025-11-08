<?php

namespace App\Http\Resources\Api\V1\PublicBus;

use Illuminate\Http\Resources\Json\JsonResource;

class StopResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->getTranslation('name', app()->getLocale()),
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }
} 