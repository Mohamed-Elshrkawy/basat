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

            // محطات الذهاب
            'outbound_journey' => [
                'boarding_stop' => [
                    'id' => $this->outboundBoardingStop->id,
                    'name' => $this->outboundBoardingStop->stop->getTranslation('name', 'ar'),
                    'arrival_time' => $this->outboundBoardingStop->arrival_time,
                    'departure_time' => $this->outboundBoardingStop->departure_time,
                    'order' => $this->outboundBoardingStop->order,
                ],
                'dropping_stop' => [
                    'id' => $this->outboundDroppingStop->id,
                    'name' => $this->outboundDroppingStop->stop->getTranslation('name', 'ar'),
                    'arrival_time' => $this->outboundDroppingStop->arrival_time,
                    'departure_time' => $this->outboundDroppingStop->departure_time,
                    'order' => $this->outboundDroppingStop->order,
                ],
            ],

            // محطات العودة (إذا كانت رحلة ذهاب وعودة)
            'return_journey' => $this->trip_type === 'round_trip' && $this->returnBoardingStop ? [
                'boarding_stop' => [
                    'id' => $this->returnBoardingStop->id,
                    'name' => $this->returnBoardingStop->stop->name,
                    'arrival_time' => $this->returnBoardingStop->arrival_time,
                    'departure_time' => $this->returnBoardingStop->departure_time,
                    'order' => $this->returnBoardingStop->order,
                ],
                'dropping_stop' => [
                    'id' => $this->returnDroppingStop->id,
                    'name' => $this->returnDroppingStop->stop->name,
                    'arrival_time' => $this->returnDroppingStop->arrival_time,
                    'departure_time' => $this->returnDroppingStop->departure_time,
                    'order' => $this->returnDroppingStop->order,
                ],
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
