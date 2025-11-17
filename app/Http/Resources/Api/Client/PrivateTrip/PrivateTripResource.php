<?php

namespace App\Http\Resources\Api\Client\PrivateTrip;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateTripResource extends JsonResource
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
            'booking_number' => $this->booking_number,
            'qr_code_url' => $this->qr_code_url,

            // Trip details
            'trip_type' => $this->trip_type,
            'travel_date' => $this->travel_date->format('Y-m-d'),
            'return_date' => $this->return_date?->format('Y-m-d'),
            'number_of_seats' => $this->number_of_seats,
            'total_days' => $this->total_days,

            // Cities
            'start_city' => [
                'id' => $this->startCity->id,
                'name' => $this->startCity->name,
            ],
            'end_city' => [
                'id' => $this->endCity->id,
                'name' => $this->endCity->name,
            ],

            // Driver details
            'driver' => [
                'id' => $this->driver->id,
                'name' => $this->driver->name,
                'phone' => $this->driver->phone,
                'avatar_url' => $this->driver->avatar_url,
            ],

            // Vehicle details
            'vehicle' => [
                'id' => $this->vehicle->id,
                'brand' => $this->vehicle->brand?->name,
                'model' => $this->vehicle->vehicleModel?->name,
                'plate_number' => $this->vehicle->plate_number,
                'seat_count' => $this->vehicle->seat_count,
                'full_info' => $this->vehicle->getFullInfo(),
            ],

            // Selected amenities
            'amenities' => $this->amenities->map(function ($amenity) {
                return [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'icon' => $amenity->icon,
                    'price' => (float) $amenity->pivot->price,
                ];
            }),

            // Pricing details
            'pricing' => [
                'distance_km' => (float) $this->distance_km,
                'base_fare' => (float) $this->base_fare,
                'amenities_cost' => (float) $this->amenities_cost,
                'discount' => (float) $this->discount,
                'total_amount' => (float) $this->total_amount,
            ],

            // Payment details
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'transaction_id' => $this->transaction_id,
                'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            ],

            // Status
            'status' => $this->status,
            'trip_status' => $this->trip_status,

            // Timestamps
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,

            // Notes
            'notes' => $this->notes,
            'driver_notes' => $this->driver_notes,

            // Created at
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
