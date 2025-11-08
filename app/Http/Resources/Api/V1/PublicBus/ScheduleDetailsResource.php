<?php

namespace App\Http\Resources\Api\V1\PublicBus;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleDetailsResource extends JsonResource
{
    public static $wrap = 'data';
    protected $bookedSeats;

    public function __construct($resource, $bookedSeats = [])
    {
        parent::__construct($resource);
        $this->bookedSeats = $bookedSeats;
    }

    public function toArray($request): array
    {
        $vehicle = $this->vehicle;
        $seats = [];
        for ($i = 1; $i <= $vehicle->seat_count; $i++) {
            $seats[] = [
                'seat_number' => $i,
                'is_available' => !in_array($i, $this->bookedSeats),
            ];
        }

        return [
            'id' => $this->id,
            'departure_time' => date('h:i A', strtotime($this->departure_time)),
            'arrival_time' => date('h:i A', strtotime($this->arrival_time)),
            'route' => new RouteResource($this->whenLoaded('route')),
            'vehicle' => [
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'total_seats' => $vehicle->seat_count,
            ],
            'stops' => $this->when(
                $this->relationLoaded('route') && $this->route->relationLoaded('stops'),
                StopResource::collection($this->route->stops)
            ),
            'seats' => $seats,
        ];
    }
}