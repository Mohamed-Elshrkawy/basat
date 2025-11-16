<?php

namespace App\Http\Resources\Api\Client\BookingSeat;

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
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'qr_code' => $this->qr_code_url,
            'schedule' => [
                'route' => [
                    'route' => $this->schedule->route->name,
                    'from' => $this->schedule->route->startCity->name,
                    'to' => $this->schedule->route->endCity->name,
                ],
                'departure_time' => $this->schedule->departure_time,
                'arrival_time' => $this->schedule->arrival_time,
                'return_departure_time' => $this->schedule->return_departure_time,
                'return_arrival_time' => $this->schedule->return_arrival_time,
                'driver' => $this->schedule->driver ? [
                    'id' => $this->schedule->driver->id,
                    'name' => $this->schedule->driver->name,
                ] : null,
            ],
            'outbound_stops' => [
                'boarding' => [
                    'id' => $this->outboundBoardingStop->id,
                    'name' => $this->outboundBoardingStop->stop->name,
                    'time' => $this->outboundBoardingStop->departure_time,
                ],
                'dropping' => [
                    'id' => $this->outboundDroppingStop->id,
                    'name' => $this->outboundDroppingStop->stop->name,
                    'time' => $this->outboundDroppingStop->arrival_time,
                ],
            ],

            // معلومات المحطات للعودة
            'return_stops' => $this->trip_type === 'round_trip' ? [
                'boarding' => [
                    'id' => $this->returnBoardingStop->id,
                    'name' => $this->returnBoardingStop->stop->name,
                    'time' => $this->returnBoardingStop->departure_time,
                ],
                'dropping' => [
                    'id' => $this->returnDroppingStop->id,
                    'name' => $this->returnDroppingStop->stop->name,
                    'time' => $this->returnDroppingStop->arrival_time,
                ],
            ] : null,
            'travel_date' => $this->travel_date->format('Y-m-d'),
            'travel_date_formatted' => $this->travel_date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
            'trip_type' => $this->trip_type,
            'trip_type_label' => $this->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'number_of_seats' => $this->number_of_seats,
            'seat_numbers' => $this->seat_numbers,
            'pricing' => [
                'outbound_fare' => (float) $this->outbound_fare,
                'return_fare' => (float) $this->return_fare,
                'discount' => (float) $this->discount,
                'total_amount' => (float) $this->total_amount,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'status' => $this->payment_status,
                'transaction_id' => $this->transaction_id,
                'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            ],
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'notes' => $this->notes,
            'cancellation' => [
                'reason' => $this->cancellation_reason,
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
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
