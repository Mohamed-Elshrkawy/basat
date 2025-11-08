<?php

namespace App\Http\Resources\Api\V1\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\V1\ChildResource;

class TripDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $passengers = [];
        $pickupAddress = $this->pickup_address;
        $dropoffAddress = $this->dropoff_address;

        if ($this->type === 'public_bus' || $this->type === 'private_hire') {
            $passengers = $this->bookings->map(function ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'name' => $booking->rider->name,
                    'mobile' => $booking->rider->mobile,
                    'seat_number' => $booking->seat_number,
                    'checked_in_at' => $booking->checked_in_at,
                ];
            });

            // For public bus trips, get addresses from the first booking's stops
            if ($this->type === 'public_bus' && $this->bookings->isNotEmpty()) {
                $firstBooking = $this->bookings->first();
                $firstBooking->load(['pickupStop', 'dropoffStop']);
                $locale = app()->getLocale();
                $pickupAddress = $firstBooking->pickupStop ? $firstBooking->pickupStop->getTranslation('name', $locale) : null;
                $dropoffAddress = $firstBooking->dropoffStop ? $firstBooking->dropoffStop->getTranslation('name', $locale) : null;
            }
        } elseif ($this->type === 'school_service') {
            $tripDate = $request->query('trip_date', now()->format('Y-m-d'));
            $passengers = $this->schoolSubscriptions->map(function ($subscription) use ($tripDate) {
                $dailyStatus = $subscription->daily_status ?? [];
                return [
                    'subscription_id' => $subscription->id,
                    'child' => new ChildResource($subscription->child),
                    'parent' => [
                        'name' => $subscription->child->parent->name,
                        'mobile' => $subscription->child->parent->mobile,
                    ],
                    'status_today' => $dailyStatus[$tripDate] ?? 'pending', // pending, picked_up, absent
                ];
            });
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'trip_datetime' => $this->trip_datetime ? $this->trip_datetime->toIso8601String() : null,
            'pickup_address' => $pickupAddress,
            'dropoff_address' => $dropoffAddress,
            'pickup_location' => ['lat' => $this->pickup_lat, 'lng' => $this->pickup_lng],
            'dropoff_location' => ['lat' => $this->dropoff_lat, 'lng' => $this->dropoff_lng],
            'total_fare' => $this->total_fare,
            'driver_earning' => $this->driver_earning,
            'rider' => $this->rider ? [
                'name' => $this->rider->name,
                'mobile' => $this->rider->mobile,
            ] : null,
            'passengers' => $passengers,
            'selected_amenities' => $this->when($this->type === 'private_hire' && $this->selected_amenities, function () {
                $locale = app()->getLocale();
                return $this->selectedAmenities()->map(function ($amenity) use ($locale) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->getTranslation('name', $locale),
                        'icon' => $amenity->icon,
                    ];
                });
            }),
        ];
    }
}