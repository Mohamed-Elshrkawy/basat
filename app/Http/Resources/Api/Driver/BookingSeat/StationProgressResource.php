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
                'name' => $this->scheduleStop->stop->getTranslation('name', 'ar'),
//                'city' => $this->scheduleStop->stop->city->getTranslation('name', 'ar'),
//                'city_id' => $this->scheduleStop->stop->city_id,
                'order' => $this->scheduleStop->order,
                'arrival_time' => $this->scheduleStop->arrival_time,
                'departure_time' => $this->scheduleStop->departure_time,
            ],

            // الحالة
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // الأوقات الفعلية
            'arrived_at' => $this->arrived_at?->format('Y-m-d H:i:s'),
            'departed_at' => $this->departed_at?->format('Y-m-d H:i:s'),

            // الركاب
            'passengers_boarded' => $this->passengers_boarded,
            'passengers_alighted' => $this->passengers_alighted,

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
}
