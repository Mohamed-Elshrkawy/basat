<?php

namespace App\Http\Resources\Api\V1\PublicBus;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Booking;
use App\Models\PublicBusSchedule;
use App\Models\Trip;
use App\Models\Setting;

class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        $tripDate = $request->query('trip_date', today()->toDateString());
        $tripIds = Trip::where('tripable_id', $this->id)
            ->where('tripable_type', PublicBusSchedule::class)
            ->whereDate('trip_datetime', $tripDate)
            ->pluck('id');

        $bookedSeatsCount = Booking::whereIn('trip_id', $tripIds)->count();

        $stops = $this->whenLoaded('route', function () {
            return $this->route->stops->sortBy('order');
        });

        $firstStop = $stops ? $stops->first() : null;
        $lastStop = $stops ? $stops->last() : null;

        static $settings = null;
        if ($settings === null) {
            $settings = Setting::first();
        }

        return [
            'id' => $this->id,
            'departure_time' => date('g:i A', strtotime($this->departure_time)),
            'arrival_time' => date('g:i A', strtotime($this->arrival_time)),
            'fare' => (float) $this->fare,
            'tax_percentage' => (float) ($settings->tax_percentage_public ?? 0),
            'app_fee_percentage' => (float) ($settings->app_fee_percentage_public ?? 0),
            'route' => new RouteResource($this->whenLoaded('route')),
            'vehicle' => $this->whenLoaded('vehicle', [
                'type' => $this->vehicle->type,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'description' => "{$this->vehicle->brand}, {$this->vehicle->model}, {$this->vehicle->plate_number}",
                'amenities' => $this->vehicle->amenities->map(function ($amenity) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->getTranslation('name', app()->getLocale()),
                        'price' => 0.0,
                    ];
                })->values(),
            ]),
            'available_seats' => $this->whenLoaded('vehicle', $this->vehicle->seat_count - $bookedSeatsCount),
            'pickup_stop' => $firstStop ? new StopResource($firstStop) : null,
            'dropoff_stop' => $lastStop ? new StopResource($lastStop) : null,
        ];
    }
} 