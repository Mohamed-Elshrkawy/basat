<?php

namespace App\Http\Resources\Api\Client\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndexBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $driver =$this->type == 'public_bus'? $this->schedule->driver: $this->driver;

        $boarding = $this->type == 'public_bus'? $this->outboundBoardingStop?->stop : $this->startCity;

        $dropping = $this->type == 'public_bus'? $this->outboundDroppingStop?->stop : $this->endCity;

        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'type' => $this->type,
            'type_label' => $this->type == 'public_bus' ? __('Public Bus') : __('Private Bus'),
            'travel_date' => $this->travel_date,
            'travel_date_formatted' => $this->travel_date->translatedFormat('D, d M Y h:i A'),
            'trip_type' => $this->trip_type,
            'trip_type_label' => $this->trip_type == 'one_way' ? __('One Way') : __('Round Trip'),
            'driver' =>  [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'avatar_url' => $driver->avatar_url,
            ],
            'boarding' => [
                'id' => $boarding->id,
                'name' => $boarding->name,
            ],
            'dropping' => [
                'id' => $dropping->id,
                'name' => $dropping->name,
            ],

            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('Waiting for payment'),
            'confirmed' => __('Confirmed'),
            'cancelled' => __('Cancelled'),
            'completed' => __('Completed'),
            'refunded' => __('Refunded'),
            default => $this->status,
        };
    }
}
