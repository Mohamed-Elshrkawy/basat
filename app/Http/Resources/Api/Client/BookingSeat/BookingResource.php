<?php

namespace App\Http\Resources\Api\Client\BookingSeat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'qr_code' => $this->qr_code_url,
            'route' => [
                'route' => $this->schedule->route->name,
                'from' => $this->schedule->route->startCity->name,
                'to' => $this->schedule->route->endCity->name,
            ],
            'outbound_stops' => [
                'boarding' => [
                    'id' => $this->outboundBoardingStop->id,
                    'name' => $this->outboundBoardingStop->stop->name,
                    'time' => $this->outboundBoardingStop->departure_time,
                    'time_formatted' => $this->outboundBoardingStop->departure_time->translatedFormat('h:i A'),
                ],
                'dropping' => [
                    'id' => $this->outboundDroppingStop->id,
                    'name' => $this->outboundDroppingStop->stop->name,
                    'time' => $this->outboundDroppingStop->arrival_time,
                    'time_formatted' => $this->outboundDroppingStop->arrival_time->translatedFormat('h:i A'),
                ],
            ],

            // معلومات المحطات للعودة
            'return_stops' => $this->trip_type === 'round_trip' ? [
                'boarding' => [
                    'id' => $this->returnBoardingStop->id,
                    'name' => $this->returnBoardingStop->stop->name,
                    'time' => $this->returnBoardingStop->departure_time,
                    'time_formatted' => $this->returnBoardingStop->departure_time->translatedFormat('h:i A'),
                ],
                'dropping' => [
                    'id' => $this->returnDroppingStop->id,
                    'name' => $this->returnDroppingStop->stop->name,
                    'time' => $this->returnDroppingStop->arrival_time,
                    'time_formatted' => $this->returnDroppingStop->arrival_time->translatedFormat('h:i A'),
                ],
            ] : null,
            'travel_date' => $this->travel_date->format('Y-m-d'),
            'travel_date_formatted' => $this->travel_date->translatedFormat('D, d M Y h:i A'),
            'trip_type' => $this->trip_type,
            'trip_type_label' => $this->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'number_of_seats' => $this->number_of_seats,
            'seat_numbers' => $this->seat_numbers,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
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
