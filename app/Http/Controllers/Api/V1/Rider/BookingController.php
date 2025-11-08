<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\StoreBookingRequest;
use App\Http\Requests\Api\V1\Rider\StorePrivateHireRequest;
use App\Models\Booking;
use App\Models\PublicBusSchedule;
use App\Models\Trip;
use App\Models\User;
use App\Models\Setting;
use App\Models\Vehicle;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Notifications\GeneralNotification;

class BookingController extends Controller
{
    public function storePublicBusBooking(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rider = $request->user();
        $schedule = PublicBusSchedule::with('vehicle', 'route.stops')->findOrFail($data['schedule_id']);
        $settings = Setting::first();

        $seatCount = count($data['seat_numbers']);
        $baseFare = $schedule->fare * $seatCount;
        $taxPercentage = $settings ? $settings->tax_percentage_public : 0;
        $taxAmount = $baseFare * ($taxPercentage / 100);
        $appFeePercentage = $settings ? $settings->app_fee_percentage_public : 0;
        $appFee = $baseFare * ($appFeePercentage / 100);
        $totalFare = $baseFare + $taxAmount + $appFee;

        // Calculate driver's earning
        $driverEarning = $totalFare - $taxAmount - $appFee;
        if ($data['payment_method'] === 'wallet') {
            if ($rider->wallet->balance < $totalFare) {
                return response()->json(['message' => __('messages.insufficient_wallet_balance')], 422);
            }
        }

        return DB::transaction(function() use ($data, $rider, $schedule, $totalFare, $baseFare, $taxPercentage, $taxAmount, $appFeePercentage, $appFee, $seatCount, $driverEarning) {
            if ($data['payment_method'] === 'wallet') {
                $rider->wallet->decrement('balance', $totalFare);
            }

            $pickupStop = $schedule->route->stops->find($data['pickup_stop_id']);
            $dropoffStop = $schedule->route->stops->find($data['dropoff_stop_id']);

            $distance = null;
            $duration = null;
            if ($pickupStop && $dropoffStop) {
                $distance = $this->calculateDistance(
                    $pickupStop->lat, $pickupStop->lng,
                    $dropoffStop->lat, $dropoffStop->lng
                );
                $duration = $distance ? round(($distance / 40) * 60) : null;
            }

            $trip = Trip::create([
                'rider_id' => $rider->id,
                'driver_id' => $schedule->driver_id,
                'vehicle_id' => $schedule->vehicle_id,
                'type' => 'public_bus',
                'status' => 'approved',
                'trip_datetime' => $data['trip_date'] . ' ' . $schedule->departure_time,
                'pickup_address' => $pickupStop ? $pickupStop->name : null,
                'pickup_lat' => $pickupStop ? $pickupStop->lat : null,
                'pickup_lng' => $pickupStop ? $pickupStop->lng : null,
                'dropoff_address' => $dropoffStop ? $dropoffStop->name : null,
                'dropoff_lat' => $dropoffStop ? $dropoffStop->lat : null,
                'dropoff_lng' => $dropoffStop ? $dropoffStop->lng : null,
                'distance' => $distance,
                'duration' => $duration,
                'base_fare' => $baseFare,
                'tax_percentage' => $taxPercentage,
                'tax_amount' => $taxAmount,
                'app_fee_percentage' => $appFeePercentage,
                'app_fee' => $appFee,
                'total_fare' => $totalFare,
                'driver_earning' => $driverEarning,
                'payment_method' => $data['payment_method'],
                'payment_status' => ($data['payment_method'] === 'wallet') ? 'paid' : 'pending',
                'tripable_id' => $schedule->id,
                'tripable_type' => PublicBusSchedule::class,
            ]);

            if ($data['payment_method'] === 'wallet') {
                $rider->wallet->transactions()->create([
                    'amount' => -$totalFare,
                    'type' => 'payment',
                    'description' => [
                        'en' => "Payment for Public Bus Trip #{$trip->id}",
                        'ar' => "دفعة رحلة باص عام رقم #{$trip->id}"
                    ],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);
            }

            $createdBookings = [];
            foreach ($data['seat_numbers'] as $seatNumber) {
                $booking = Booking::create([
                    'trip_id' => $trip->id,
                    'schedule_id' => $schedule->id,
                    'rider_id' => $rider->id,
                    'pickup_stop_id' => $data['pickup_stop_id'],
                    'dropoff_stop_id' => $data['dropoff_stop_id'],
                    'seat_number' => $seatNumber,
                    'status' => 'confirmed',
                ]);
                $createdBookings[] = [
                    'booking_id' => $booking->id,
                    'seat_number' => $booking->seat_number,
                ];
            }

            // Send notifications
            $trip->driver->notify(new GeneralNotification(
                title: ['en' => 'New Booking', 'ar' => 'حجز جديد'],
                body: ['en' => "You have a new booking #{$trip->id} from {$rider->name}.", 'ar' => "لديك حجز جديد رقم #{$trip->id} من {$rider->name}."],
                data: [
                    'type' => 'new_booking',
                    'trip_id' => $trip->id,
                ]
            ));

            return response()->json([
                'status' => true,
                'code' => 'booking_success',
                'message' => __('messages.booking_success'),
                'data' => [
                    'trip_id' => $trip->id,
                    'bookings' => $createdBookings,
                    'total_fare' => $totalFare,
                    'departure_time' => $schedule->departure_time,
                    'arrival_time' => $schedule->arrival_time,
                    'payment_method' => $data['payment_method'],
                ]
            ], 201);
        });
    }

    public function storePrivateHireBooking(StorePrivateHireRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rider = $request->user();
        $driver = User::findOrFail($data['driver_id']);
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        $amenitiesFare = 0;
        if (!empty($data['amenity_ids'])) {
            // Convert comma-separated string to array
            $amenityIds = is_string($data['amenity_ids'])
                ? array_filter(array_map('intval', explode(',', $data['amenity_ids'])))
                : $data['amenity_ids'];

            if (!empty($amenityIds)) {
                $amenitiesFare = $vehicle->amenities()
                    ->whereIn('amenities.id', $amenityIds)
                    ->sum('price');
            }
        }

        $baseFare = $data['estimated_price'] ?? 250.00;
        $totalFare = $baseFare + $amenitiesFare;
        // Calculate driver's earning for private hire
        $settings = Setting::first();
        $taxPercentage = $settings ? $settings->tax_percentage_private ?? 0 : 0;
        $appFeePercentage = $settings ? $settings->app_fee_percentage_private ?? 0 : 0;
        $taxAmount = $totalFare * ($taxPercentage / 100);
        $appFee = $totalFare * ($appFeePercentage / 100);
        $driverEarning = $totalFare - $taxAmount - $appFee;

        if ($data['payment_method'] === 'wallet') {
            if ($rider->wallet->balance < $totalFare) {
                return response()->json(['message' => __('messages.insufficient_wallet_balance')], 422);
            }
        }

        return DB::transaction(function() use ($data, $rider, $driver, $totalFare, $baseFare, $amenitiesFare, $request, $driverEarning, $taxAmount, $appFee) {
            // تحضير الخدمات الإضافية المختارة
            $selectedAmenities = null;
            if (!empty($data['amenity_ids'])) {
                $amenityIds = is_string($data['amenity_ids'])
                    ? array_filter(array_map('intval', explode(',', $data['amenity_ids'])))
                    : $data['amenity_ids'];
                $selectedAmenities = !empty($amenityIds) ? $amenityIds : null;
            }
            if ($data['payment_method'] === 'wallet') {
                $rider->wallet->decrement('balance', $totalFare);
            }

            $trip = Trip::create([
                'rider_id' => $rider->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $data['vehicle_id'],
                'type' => 'private_hire',
                'status' => 'approved',
                'trip_datetime' => $data['trip_datetime'],
                'pickup_address' => $data['pickup_address'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'dropoff_address' => $data['dropoff_address'],
                'dropoff_lat' => $data['dropoff_lat'],
                'dropoff_lng' => $data['dropoff_lng'],
                'distance' => $data['distance'] ?? null,
                'duration' => $data['duration'] ?? null,
                'base_fare' => $baseFare,
                'amenities_fare' => $amenitiesFare,
                'selected_amenities' => $selectedAmenities,
                'total_fare' => $totalFare,
                'tax_amount' => $taxAmount,
                'app_fee' => $appFee,
                'driver_earning' => $driverEarning,
                'payment_method' => $data['payment_method'],
                'payment_status' => ($data['payment_method'] === 'wallet') ? 'paid' : 'pending',
                'responsible_person_name' => $data['responsible_person_name'] ?? null,
            ]);

            if ($data['payment_method'] === 'wallet') {
                $rider->wallet->transactions()->create([
                    'amount' => -$totalFare,
                    'type' => 'payment',
                    'description' => [
                        'en' => "Payment for Private Hire Trip #{$trip->id}",
                        'ar' => "دفعة رحلة خاصة رقم #{$trip->id}"
                    ],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);
            }

            if ($request->hasFile('responsible_person_id_photo')) {
                $trip->addMediaFromRequest('responsible_person_id_photo')->toMediaCollection('responsible_person_id');
            }

            // Send Notifications
            $driver->notify(new GeneralNotification(
                title: ['en' => 'New Private Hire', 'ar' => 'حجز باص خاص جديد'],
                body: ['en' => "You have a new private hire booking #{$trip->id} from {$rider->name}.", 'ar' => "لديك حجز باص خاص جديد رقم #{$trip->id} من {$rider->name}."],
                data: [
                    'type' => 'new_private_hire',
                    'trip_id' => $trip->id,
                ]
            ));

            return response()->json([
                'status' => true,
                'code' => 'booking_success',
                'message' => __('messages.private_hire_booked_successfully'),
                'data' => [
                    'trip_id' => $trip->id,
                    'total_fare' => $totalFare,
                ]
            ], 201);
        });
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return round($earthRadius * $c, 2);
    }
}
