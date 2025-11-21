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
            'trip_date_formatted' => $this->trip_date->translatedFormat('D d M Y h:i A'),
            'day_name' => $this->trip_date->locale('ar')->dayName,

            // المسار
            'route' => [
                'id' => $this->schedule->route->name,
                'from' => $this->schedule->route->startCity->name,
                'to' => $this->schedule->route->endCity->name,
            ],

            // الأوقات المخططة
            'scheduled_times' => [
                'departure' => $this->schedule->departure_time,
                'departure_formatted' => $this->schedule->departure_time->translatedFormat('h:i A'),
                'arrival' => $this->schedule->arrival_time,
                'arrival_formatted' => $this->schedule->arrival_time->translatedFormat('h:i A'),
            ],

            // الأوقات الفعلية
            'actual_times' => [
                'started_at' => $this->started_at?->translatedFormat('D d M Y h:i A'),
                'completed_at' => $this->completed_at?->translatedFormat('D d M Y h:i A'),
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
                'no_show' => $this->no_show_passengers,
                'bookings' => PassengerResource::collection(
                    $this->bookings()
                        ->with([
                            'user',
                            'outboundBoardingStop.stop',
                            'outboundDroppingStop.stop',
                            'returnBoardingStop.stop',
                            'returnDroppingStop.stop'
                        ])
                        ->get()
                ),
            ],
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
