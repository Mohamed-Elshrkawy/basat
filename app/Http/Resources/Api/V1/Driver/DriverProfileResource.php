<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Logic to determine current service type
        $serviceType = 'Not defined';
        $currentTrip = $this->tripsAsDriver()
            ->where('status', 'on_way')
            ->orWhere(function($query) {
                $query->whereIn('status', ['approved', 'pending'])
                      ->where('trip_datetime', '>', now());
            })
            ->orderBy('trip_datetime', 'asc')
            ->first();

        if ($currentTrip) {
            $types = [
                'public_bus' => 'Public Bus',
                'private_hire' => 'Private Hire',
                'school_service' => 'School Service',
            ];
            $serviceType = $types[$currentTrip->type] ?? 'Not defined';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'national_id' => $this->national_id,
            'mobile_verified_at' => $this->mobile_verified_at ? $this->mobile_verified_at->toDateTimeString() : null,
            'profile' => [
                'bio' => $this->driverProfile->bio ?? null,
                'avg_rating' => (float) ($this->driverProfile->avg_rating ?? 0),
                'availability_status' => $this->driverProfile->availability_status ?? null,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'service_type' => $serviceType,
            'avatar_url' => $this->avatar_url,
        ];
    }
} 