<?php
namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'trip_datetime' => $this->trip_datetime ? $this->trip_datetime->toIso8601String() : null,
            'pickup_address' => $this->pickup_address,
            'dropoff_address' => $this->dropoff_address,
            'rider' => $this->whenLoaded('rider', [
                'name' => $this->rider->name,
                'mobile' => $this->rider->mobile,
            ]),
            'earning' => $this->driver_earning,
            'total_fare' => $this->total_fare,
        ];
    }
} 