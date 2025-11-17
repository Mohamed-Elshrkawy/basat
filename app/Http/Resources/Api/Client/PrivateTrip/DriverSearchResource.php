<?php

namespace App\Http\Resources\Api\Client\PrivateTrip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverSearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,

            // Driver details
            'driver' => [
                'id' => $this->driver->id,
                'rating' => round($this->driver->rating ?? 0, 1),
                'total_trips' => $this->driver->total_trips ?? 0,
                'experience_years' => $this->driver->experience_years,
            ],

            // Vehicle details
            'vehicle' => [
                'id' => $this->vehicle->id,
                'brand' => $this->vehicle->brand?->name,
                'model' => $this->vehicle->vehicleModel?->name,
                'plate_number' => $this->vehicle->plate_number,
                'seat_count' => $this->vehicle->seat_count,
                'type' => $this->vehicle->type,
                'full_info' => $this->vehicle->getFullInfo(),

                // Available amenities
                'amenities' => $this->vehicle->amenities->map(function ($amenity) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->icon,
                        'description' => $amenity->description,
                        'price' => (float) $amenity->pivot->price,
                        'is_free' => $amenity->pivot->price == 0,
                    ];
                }),
            ],
        ];
    }
}
