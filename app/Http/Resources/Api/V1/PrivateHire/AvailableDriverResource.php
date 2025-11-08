<?php

namespace App\Http\Resources\Api\V1\PrivateHire;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailableDriverResource extends JsonResource
{
    public function toArray($request): array
    {
        $vehicle = $this->user->vehicles->first();
        return [
            'driver_id' => $this->user_id,
            'name' => $this->user->name,
            'avg_rating' => $this->avg_rating,
            'vehicle' => [
                'id' => $vehicle->id,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'type' => $vehicle->type,
                'seat_count' => $vehicle->seat_count,
                'plate_number' => $vehicle->plate_number,
            ],
            'amenities' => $vehicle->amenities->map(function ($amenity) {
                return [
                    'id' => $amenity->id,
                    'name' => $amenity->getTranslation('name', 'ar'),
                    'price' => $amenity->pivot->price,
                ];
            }),
            'estimated_price' => $this->calculatePrice(),
        ];
    }

    private function calculatePrice()
    {
        return 250.00;
    }
} 