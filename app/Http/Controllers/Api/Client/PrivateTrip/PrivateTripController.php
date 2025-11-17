<?php

namespace App\Http\Controllers\Api\Client\PrivateTrip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\PrivateTrip\BookPrivateTripRequest;
use App\Http\Requests\Api\Client\PrivateTrip\SearchPrivateTripRequest;
use App\Http\Resources\Api\Client\PrivateTrip\PrivateTripResource;
use App\Http\Resources\Api\Client\PrivateTrip\DriverSearchResource;
use App\Models\City;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Amenity;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrivateTripController extends Controller
{
    /**
     * Base rate per kilometer (in SAR)
     */
    const BASE_RATE_PER_KM = 2.5;

    /**
     * Minimum fare (in SAR)
     */
    const MINIMUM_FARE = 100;

    /**
     * Search for available private bus drivers
     */
    public function search(SearchPrivateTripRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get start and end cities
        $startCity = City::findOrFail($validated['start_city_id']);
        $endCity = City::findOrFail($validated['end_city_id']);

        // Calculate distance between cities
        $distance = $this->calculateDistance(
            $startCity->lat,
            $startCity->lng,
            $endCity->lat,
            $endCity->lng
        );

        // Find available private bus drivers
        $drivers = User::where('is_active', true)
        ->where('status', 'active')
        ->whereHas('driver')
        ->whereHas('vehicle', function ($query) use ($validated) {
            $query->where('type', 'private_bus')
                ->where('is_active', true)
                ->where('seat_count', '>=', $validated['number_of_seats']);
        })
        ->whereHas('cities', function ($query) use ($validated) {
            // Driver must operate in both start and end cities
            $query->whereIn('cities.id', [
                $validated['start_city_id'],
                $validated['end_city_id']
            ]);
        }, '=', 2) // Must have both cities
        ->with([
            'driver',
            'vehicle.brand',
            'vehicle.vehicleModel',
            'vehicle.amenities' => function ($query) {
                $query->where('is_active', true);
            }
        ])
        ->get()
        ->filter(function ($driver) use ($validated) {
            // Check driver availability for the requested dates
            return $this->isDriverAvailable(
                $driver->id,
                $validated['travel_date'],
                $validated['return_date'] ?? null
            );
        });

        return json( DriverSearchResource::collection($drivers));

    }

    /**
     * Calculate price for a specific trip configuration
     */
    public function calculatePrice(BookPrivateTripRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get driver and vehicle
        $driver = User::with(['vehicle.amenities'])->findOrFail($validated['driver_id']);

        if (!$driver->vehicle || $driver->vehicle->type !== 'private_bus') {
            return json(__('Driver does not have a private bus'), status: 'fail', headerStatus: 422);
        }

        // Get cities
        $startCity = City::findOrFail($validated['start_city_id']);
        $endCity = City::findOrFail($validated['end_city_id']);

        // Calculate distance
        $distance = $this->calculateDistance(
            $startCity->lat,
            $startCity->lng,
            $endCity->lat,
            $endCity->lng
        );

        // Calculate total days
        $totalDays = 1;
        if ($validated['trip_type'] === 'round_trip' && !empty($validated['return_date'])) {
            $travelDate = \Carbon\Carbon::parse($validated['travel_date']);
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $totalDays = max(1, $travelDate->diffInDays($returnDate) + 1);
        }

        // Calculate base fare
        $baseFare = max(self::MINIMUM_FARE, $distance * self::BASE_RATE_PER_KM * $totalDays);

        // Calculate amenities cost
        $amenitiesCost = 0;
        $selectedAmenities = [];

        if (!empty($validated['amenity_ids'])) {
            $vehicleAmenities = $driver->vehicle->amenities()
                ->whereIn('amenities.id', $validated['amenity_ids'])
                ->get();

            foreach ($vehicleAmenities as $amenity) {
                $price = $amenity->pivot->price ?? 0;
                $amenitiesCost += $price * $totalDays;

                $selectedAmenities[] = [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'price_per_day' => (float) $price,
                    'total_price' => (float) ($price * $totalDays),
                ];
            }
        }

        // Calculate total
        $discount = 0; // Can be implemented later based on promotions
        $totalAmount = $baseFare + $amenitiesCost - $discount;

        return json( [
                'distance_km' => round($distance, 2),
                'total_days' => $totalDays,
                'base_fare' => round($baseFare, 2),
                'amenities_cost' => round($amenitiesCost, 2),
                'discount' => round($discount, 2),
                'total_amount' => round($totalAmount, 2),
                'breakdown' => [
                    'rate_per_km' => self::BASE_RATE_PER_KM,
                    'distance_calculation' => round($distance * self::BASE_RATE_PER_KM, 2),
                    'days_multiplier' => $totalDays,
                    'selected_amenities' => $selectedAmenities,
                ]
            ]
        );
    }

    /**
     * Book a private trip
     */
    public function store(BookPrivateTripRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        if(!setting('enable_private_bus'))
        {
            return json(__('Private bus is disabled'), status: 'fail', headerStatus: 422);
        }

        // Verify driver and vehicle
        $driver = User::with(['vehicle.amenities'])->findOrFail($validated['driver_id']);

        if (!$driver->vehicle || $driver->vehicle->type !== 'private_bus') {
            return json(__('Driver does not have a private bus'), status: 'fail', headerStatus: 422);
        }

        // Check vehicle capacity
        if ($driver->vehicle->seat_count < $validated['number_of_seats']) {
            return json(__('Vehicle does not have enough seats'), status: 'fail', headerStatus: 422);
        }

        // Check driver availability
        if (!$this->isDriverAvailable($validated['driver_id'], $validated['travel_date'], $validated['return_date'] ?? null)) {
            return json(__('Driver is not available for the selected dates'), status: 'fail', headerStatus: 422);
        }

        // Get cities
        $startCity = City::findOrFail($validated['start_city_id']);
        $endCity = City::findOrFail($validated['end_city_id']);

        // Calculate distance
        $distance = $this->calculateDistance(
            $startCity->lat,
            $startCity->lng,
            $endCity->lat,
            $endCity->lng
        );

        // Calculate total days
        $totalDays = 1;
        if ($validated['trip_type'] === 'round_trip' && !empty($validated['return_date'])) {
            $travelDate = \Carbon\Carbon::parse($validated['travel_date']);
            $returnDate = \Carbon\Carbon::parse($validated['return_date']);
            $totalDays = max(1, $travelDate->diffInDays($returnDate) + 1);
        }

        // Calculate base fare
        $baseFare = max(self::MINIMUM_FARE, $distance * self::BASE_RATE_PER_KM * $totalDays);

        // Calculate amenities cost
        $amenitiesCost = 0;
        $amenitiesData = [];

        if (!empty($validated['amenity_ids'])) {
            $vehicleAmenities = $driver->vehicle->amenities()
                ->whereIn('amenities.id', $validated['amenity_ids'])
                ->get();

            foreach ($vehicleAmenities as $amenity) {
                $price = $amenity->pivot->price ?? 0;
                $amenitiesCost += $price * $totalDays;
                $amenitiesData[$amenity->id] = ['price' => $price * $totalDays];
            }
        }

        // Calculate total
        $discount = 0;
        $totalAmount = $baseFare + $amenitiesCost - $discount;

        try {
            DB::beginTransaction();

            // Create the private trip booking
            $booking = Booking::create([
                'type' => 'private_bus',
                'user_id' => $user->id,
                'driver_id' => $validated['driver_id'],
                'vehicle_id' => $driver->vehicle->id,
                'start_city_id' => $validated['start_city_id'],
                'end_city_id' => $validated['end_city_id'],
                'trip_type' => $validated['trip_type'],
                'travel_date' => $validated['travel_date'],
                'return_date' => $validated['return_date'] ?? null,
                'number_of_seats' => $validated['number_of_seats'],
                'seat_numbers' => [], // Empty array for private trips (no specific seat numbers)
                'total_days' => $totalDays,
                'distance_km' => $distance,
                'base_fare' => $baseFare,
                'amenities_cost' => $amenitiesCost,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
                'status' => 'pending',
                'trip_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Attach amenities if any
            if (!empty($amenitiesData)) {
                $booking->amenities()->attach($amenitiesData);
            }

            // Process payment if wallet
            if ($validated['payment_method'] === 'wallet') {
                $user->withdraw(
                    $totalAmount,
                    [
                        'ar' => 'دفع رحلة خاصة رقم ' . $booking->booking_number,
                        'en' => 'Payment for private trip ' . $booking->booking_number
                    ]
                );

                $booking->markAsPaid('WALLET_' . now()->timestamp);
            }

            DB::commit();

            // Load relationships for response
            $booking->load([
                'driver',
                'vehicle.brand',
                'vehicle.vehicleModel',
                'startCity',
                'endCity',
                'amenities'
            ]);

            return json( new PrivateTripResource($booking));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to book private trip'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if driver is available for the given dates
     */
    private function isDriverAvailable(int $driverId, string $travelDate, ?string $returnDate): bool
    {
        $endDate = $returnDate ?? $travelDate;

        // Check for conflicting trips
        $hasConflict = Booking::where('type', 'private_bus')
            ->where('driver_id', $driverId)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->where(function ($query) use ($travelDate, $endDate) {
                $query->whereBetween('travel_date', [$travelDate, $endDate])
                    ->orWhereBetween('return_date', [$travelDate, $endDate])
                    ->orWhere(function ($q) use ($travelDate, $endDate) {
                        $q->where('travel_date', '<=', $travelDate)
                          ->where('return_date', '>=', $endDate);
                    });
            })
            ->exists();

        return !$hasConflict;
    }
}
