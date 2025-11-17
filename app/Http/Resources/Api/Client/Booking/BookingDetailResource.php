<?php

namespace App\Http\Resources\Api\Client\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $driver =$this->type == 'public_bus'? $this->schedule->driver: $this->driver;

        $vehicle =$this->type == 'public_bus'? $driver->vehicle: $this->vehicle;

        $outbound_stops_boarding = $this->type == 'public_bus'? $this->outboundBoardingStop?->stop : $this->startCity;

        $outbound_stops_dropping = $this->type == 'public_bus'? $this->outboundDroppingStop?->stop : $this->endCity;

        $return_stops_boarding = $this->type == 'public_bus'? $this->returnBoardingStop?->stop : $this->endCity;

        $return_stops_dropping = $this->type == 'public_bus'? $this->returnDroppingStop?->stop : $this->startCity;

        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,

            'qr_code' => $this->qr_code_url,

            'outbound_stops' => [
                'boarding' =>[
                    'id' => $outbound_stops_boarding->id,
                    'name' => $outbound_stops_boarding->stop->name,
                ],
                'dropping' =>[
                    'id' => $outbound_stops_dropping->id,
                    'name' => $outbound_stops_dropping->stop->name,
                ],
            ],

            'return_stops' => $this->trip_type === 'round_trip' ? [
                'boarding' => [
                    'id' => $return_stops_boarding->id,
                    'name' => $return_stops_boarding->name,
                ],
                'dropping' => [
                    'id' => $return_stops_dropping->id,
                    'name' => $return_stops_dropping->name,
                ],
            ] : [],

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

            'vehicle' => [
                'brand' => $vehicle->brand?->name,
                'model' => $vehicle->vehicleModel?->name,
                'plate_number' => $vehicle->plate_number,
                'full_info' => $vehicle->getFullInfo(),
            ],

            'amenities' => $this->amenities->map(function ($amenity) {
                return [
                    'name' => $amenity->name,
                    'icon' => $amenity->icon,
                    'price' => (float) $amenity->pivot->price,
                ];
            }),

            'number_of_seats' => $this->number_of_seats,

            'seat_numbers' => $this->seat_numbers,

            'total_amount' => round((float) $this->total_amount, 2),


            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            ],

            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

        ];
    }

    /**
     * Get status label
     */
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
