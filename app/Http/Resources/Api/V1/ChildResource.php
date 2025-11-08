<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ChildResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'photo_url' => $this->hasMedia('photo') ? $this->getFirstMediaUrl('photo') : null,
            'age' => $this->age,
        ];
    }
}