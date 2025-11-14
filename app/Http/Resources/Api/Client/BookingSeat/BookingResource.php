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
                'from' => $this->schedule->route->startCity->getTranslation('name', 'ar'),
                'to' => $this->schedule->route->endCity->getTranslation('name', 'ar'),
            ],
            'travel_date' => $this->travel_date->format('Y-m-d'),
            'travel_date_formatted' => $this->travel_date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
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
