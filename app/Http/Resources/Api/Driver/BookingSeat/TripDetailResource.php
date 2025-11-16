<?php

namespace App\Http\Resources\Api\Driver\BookingSeat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripDetailResource extends JsonResource
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
            'schedule_id' => $this->schedule_id,
            'trip_number' => "TRIP-{$this->id}",

            // التاريخ واليوم
            'trip_date' => $this->trip_date->format('Y-m-d'),
            'trip_date_formatted' => $this->trip_date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
            'day_name' => $this->trip_date->locale('ar')->dayName,

            // المسار
            'route' => [
                'id' => $this->schedule->route_id,
                'from' => $this->schedule->route->startCity->getTranslation('name', 'ar'),
                'from_id' => $this->schedule->route->start_city_id,
                'to' => $this->schedule->route->endCity->getTranslation('name', 'ar'),
                'to_id' => $this->schedule->route->end_city_id,
            ],

            // الأوقات المخططة
            'scheduled_times' => [
                'departure' => $this->schedule->departure_time,
                'arrival' => $this->schedule->arrival_time,
                'duration' => $this->schedule->duration ?? 'N/A',
            ],

            // الأوقات الفعلية
            'actual_times' => [
                'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            ],

            // الحالة
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),

            // المحطات
            'stations' => StationProgressResource::collection($this->stationProgress),

            // الركاب
            'passengers' => [
                'total' => $this->total_passengers,
                'checked_in' => $this->checked_in_passengers,
                'boarded' => $this->boarded_passengers,
                'bookings' => PassengerResource::collection($this->bookings()->with(['user', 'boardingStop.stop'])->get()),
            ],

            // ملاحظات السائق
            'driver_notes' => $this->driver_notes,

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
            'scheduled' => __('Scheduled'),
            'in_progress' => __('In Progress'),
            'completed' => __('Completed'),
            'cancelled' => __('Cancelled'),
            default => $this->status,
        };
    }
}
