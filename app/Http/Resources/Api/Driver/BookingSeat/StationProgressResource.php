<?php

namespace App\Http\Resources\Api\Driver\BookingSeat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationProgressResource extends JsonResource
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
            'stop_order' => $this->stop_order,
            'direction' => $this->direction,
            'direction_label' => $this->direction === 'outbound' ? 'ذهاب' : 'عودة',

            // معلومات المحطة
            'stop' => [
                'id' => $this->scheduleStop->stop->id,
                'name' => $this->scheduleStop->stop->name,
                'order' => $this->scheduleStop->order,
                'arrival_time' => $this->scheduleStop->arrival_time,
                'arrival_time_formatted' => $this->scheduleStop->arrival_time->translatedFormat('h:i A'),
                'departure_time' => $this->scheduleStop->departure_time,
                'departure_time_formatted' => $this->scheduleStop->departure_time->translatedFormat('h:i A'),
            ],

            // الحالة
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // الأوقات الفعلية
            'arrived_at' => $this->arrived_at?->translatedFormat('D d M Y h:i A'),
            'departed_at' => $this->departed_at?->translatedFormat('D d M Y h:i A'),

            // الركاب في هذه المحطة
            'passengers_count' => [
                'boarding' => $this->getBoardingPassengersCount(),
                'dropping' => $this->getDroppingPassengersCount(),
            ],

            // ملاحظات
            'notes' => $this->notes,
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => __('Not Arrived'),
            'arrived' => __('Arrived'),
            'departed' => __('Departed'),
            default => $this->status,
        };
    }

    /**
     * Get count of passengers boarding at this station
     */
    private function getBoardingPassengersCount(): int
    {
        $tripInstance = $this->tripInstance;

        if ($this->direction === 'outbound') {
            return $tripInstance->bookings()
                ->where('outbound_boarding_stop_id', $this->schedule_stop_id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
        } else {
            return $tripInstance->bookings()
                ->where('return_boarding_stop_id', $this->schedule_stop_id)
                ->where('trip_type', 'round_trip')
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
        }
    }

    /**
     * Get count of passengers dropping at this station
     */
    private function getDroppingPassengersCount(): int
    {
        $tripInstance = $this->tripInstance;

        if ($this->direction === 'outbound') {
            return $tripInstance->bookings()
                ->where('outbound_dropping_stop_id', $this->schedule_stop_id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
        } else {
            return $tripInstance->bookings()
                ->where('return_dropping_stop_id', $this->schedule_stop_id)
                ->where('trip_type', 'round_trip')
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
        }
    }
}
