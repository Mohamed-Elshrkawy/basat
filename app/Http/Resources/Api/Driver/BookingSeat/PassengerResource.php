<?php

namespace App\Http\Resources\Api\Driver\BookingSeat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PassengerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => $this->id,
            'booking_number' => $this->booking_number,

            // معلومات الراكب
            'passenger' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone ?? 'N/A',
                'email' => $this->user->email,
            ],

            // المقاعد
            'seat_numbers' => $this->seat_numbers,
            'number_of_seats' => $this->number_of_seats,

            // حالة الحجز
            'booking_status' => $this->status,
            'booking_status_label' => $this->getBookingStatusLabel(),

            // حالة الراكب
            'passenger_status' => $this->passenger_status,
            'passenger_status_label' => $this->getPassengerStatusLabel(),

            // الدفع
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'total_amount' => (float) $this->total_amount,

            // المحطة
            'boarding_stop' => $this->boardingStop ? [
                'id' => $this->boardingStop->id,
                'stop_name' => $this->boardingStop->stop->getTranslation('name', 'ar'),
//                'city' => $this->boardingStop->stop->city->getTranslation('name', 'ar'),
                'order' => $this->boardingStop->order,
                'direction' => $this->boardingStop->direction,
            ] : null,

            // الأوقات
            'checked_in_at' => $this->checked_in_at?->format('Y-m-d H:i:s'),
            'boarded_at' => $this->boarded_at?->format('Y-m-d H:i:s'),
            'arrived_at' => $this->arrived_at?->format('Y-m-d H:i:s'),

            // ملاحظات
            'notes' => $this->notes,
            'driver_notes' => $this->driver_notes,

            // QR Code
            'qr_code' => $this->qr_code_url,
        ];
    }

    /**
     * Get booking status label
     */
    private function getBookingStatusLabel(): string
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

    /**
     * Get passenger status label
     */
    private function getPassengerStatusLabel(): string
    {
        return match($this->passenger_status) {
            'pending' => __('Not Arrived'),
            'checked_in' => __('Checked In'),
            'boarded' => __('Boarded'),
            'completed' => __('Completed'),
            'no_show' => __('No Show'),
            default => $this->passenger_status,
        };
    }
}
