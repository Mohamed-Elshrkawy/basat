<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\SearchTripsRequest;
use App\Http\Requests\Api\V1\Rider\SearchPrivateHireRequest;
use App\Http\Resources\Api\V1\PublicBus\ScheduleResource;
use App\Http\Resources\Api\V1\PublicBus\ScheduleDetailsResource;
use App\Http\Resources\Api\V1\PrivateHire\AvailableDriverResource;
use App\Models\PublicBusSchedule;
use App\Models\Booking;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\City;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Requests\Api\V1\Rider\StoreRatingRequest;
use App\Http\Requests\Api\V1\StoreProblemReportRequest;

class TripSearchController extends Controller
{

    /**
     * Search for public bus trips based on cities, date, and time period.
     */
    public function searchPublicBusTrips(SearchTripsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $pickupCityId = $data['pickup_city_id'];
        $dropoffCityId = $data['dropoff_city_id'];
        $dayOfWeek = strtolower(Carbon::parse($data['trip_date'])->format('l'));
        $timeFrom = $data['time_from'] ?? null;
        $timeTo = $data['time_to'] ?? null;


        // Get the specified cities
        $pickupCity = City::find($pickupCityId);
        $dropoffCity = City::find($dropoffCityId);

        if (!$pickupCity || !$dropoffCity) {
            return response()->json([
                'status' => false,
                'code' => 'invalid_cities',
                'message' => 'The specified cities are invalid',
                'data' => []
            ]);
        }

        // Search for routes that pass through the specified cities
        $pickupRoutes = DB::table('stops')
            ->join('routes', 'stops.route_id', '=', 'routes.id')
            ->where('routes.is_active', true)
            ->where(function($query) use ($pickupCity) {
                $query->whereRaw("ST_Distance_Sphere(POINT(stops.lng, stops.lat), POINT(?, ?)) <= stops.range_meters",
                    [$pickupCity->lng, $pickupCity->lat]);
            })
            ->pluck('stops.route_id')
            ->unique();


        if ($pickupRoutes->isEmpty()) {
            return response()->json([
                'status' => true,
                'code' => 'no_nearby_pickup_stations',
                'message' => 'No nearby pickup stations found',
                'data' => []
            ]);
        }

        $dropoffRoutes = DB::table('stops')
            ->join('routes', 'stops.route_id', '=', 'routes.id')
            ->where('routes.is_active', true)
            ->where(function($query) use ($dropoffCity) {
                $query->whereRaw("ST_Distance_Sphere(POINT(stops.lng, stops.lat), POINT(?, ?)) <= stops.range_meters",
                    [$dropoffCity->lng, $dropoffCity->lat]);
            })
            ->pluck('stops.route_id')
            ->unique();


        if ($dropoffRoutes->isEmpty()) {
            return response()->json([
                'status' => true,
                'code' => 'no_nearby_dropoff_stations',
                'message' => 'No nearby dropoff stations found',
                'data' => []
            ]);
        }

        $validRouteIds = $pickupRoutes->intersect($dropoffRoutes)->toArray();


        if (empty($validRouteIds)) {
            return response()->json([
                'status' => true,
                'code' => 'no_direct_routes_found',
                'message' => 'No direct routes found between the specified cities',
                'data' => []
            ]);
        }

        $schedules = PublicBusSchedule::with(['route.stops', 'vehicle', 'driver'])
            ->whereIn('route_id', $validRouteIds)
            ->where('is_active', true)
            ->where(function($query) use ($dayOfWeek) {
                $query->whereJsonContains('days_of_week', $dayOfWeek)
                      ->orWhere('days_of_week', 'like', "%$dayOfWeek%");
            })
            ->when($timeFrom, function ($query, $timeFrom) {
                return $query->where('departure_time', '>=', $timeFrom);
            })
            ->when($timeTo, function ($query, $timeTo) {
                return $query->where('departure_time', '<=', $timeTo);
            })
            ->orderBy('departure_time')
            ->get();

        return response()->json([
            'status' => true,
            'code' => 'trips_found',
            'message' => __('messages.trips_found_successfully'),
            'data' => ScheduleResource::collection($schedules)
        ]);
    }

    /**
     * Get the list of available cities
     */
    public function getCities(): JsonResponse
    {
        $cities = City::active()
            ->orderBy('name')
            ->get()
            ->map(function ($city) {
                return [
                    'id' => $city->id,
                    'name' => $city->name,
                    'lat' => $city->lat,
                    'lng' => $city->lng,
                ];
            });

        return response()->json([
            'status' => true,
            'code' => 'cities_retrieved',
            'message' => 'Cities retrieved successfully',
            'data' => $cities
        ]);
    }

    /*
    public function searchPublicBusTrips(SearchTripsRequest $request): JsonResponse
    {
        $data = $request->validated();
        $pickupLat = $data['pickup_lat'];
        $pickupLng = $data['pickup_lng'];
        $dropoffLat = $data['dropoff_lat'];
        $dropoffLng = $data['dropoff_lng'];
        $dayOfWeek = strtolower(Carbon::parse($data['trip_date'])->format('l'));
        $timeFrom = $data['time_from'] ?? null;
        $timeTo = $data['time_to'] ?? null;


        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(stops.lat)) * cos(radians(stops.lng) - radians(?)) + sin(radians(?)) * sin(radians(stops.lat))))";

        $pickupRoutes = DB::table('stops')
            ->select('route_id')
            ->whereRaw("{$haversine} <= stops.range_meters / 1000", [$pickupLat, $pickupLng, $pickupLat])
            ->pluck('route_id')
            ->unique();

        if ($pickupRoutes->isEmpty()) {
            return response()->json([
                'status' => true,
                'code' => 'no_nearby_pickup_stations',
                'message' => 'No nearby pickup stations found.',
                'data' => []
            ]);
        }

        $dropoffRoutes = DB::table('stops')
            ->select('route_id')
            ->whereRaw("{$haversine} <= stops.range_meters / 1000", [$dropoffLat, $dropoffLng, $dropoffLat])
            ->pluck('route_id')
            ->unique();

        if ($dropoffRoutes->isEmpty()) {
            return response()->json([
                'status' => true,
                'code' => 'no_nearby_dropoff_stations',
                'message' => 'No nearby dropoff stations found.',
                'data' => []
            ]);
        }

        $validRouteIds = $pickupRoutes->intersect($dropoffRoutes)->toArray();

        if (empty($validRouteIds)) {
            return response()->json([
                'status' => true,
                'code' => 'no_direct_routes_found',
                'message' => 'No direct routes found.',
                'data' => []
            ]);
        }

        $schedules = PublicBusSchedule::with(['route.stops', 'vehicle', 'driver'])
            ->whereIn('route_id', $validRouteIds)
            ->where('is_active', true)
            ->where(function($query) use ($dayOfWeek) {
                $query->whereJsonContains('days_of_week', $dayOfWeek)
                      ->orWhere('days_of_week', 'like', "%$dayOfWeek%");
            })
            ->when($timeFrom, function ($query, $timeFrom) {
                return $query->where('departure_time', '>=', $timeFrom);
            })
            ->when($timeTo, function ($query, $timeTo) {
                return $query->where('departure_time', '<=', $timeTo);
            })
            ->orderBy('departure_time')
            ->get();

        return response()->json([
            'status' => true,
            'code' => 'trips_found',
            'message' => __('messages.trips_found_successfully'),
            'data' => ScheduleResource::collection($schedules)
        ]);
    }
    */

    public function getScheduleDetails(Request $request, PublicBusSchedule $schedule): JsonResponse
    {
        $request->validate(['trip_date' => 'required|date_format:Y-m-d']);
        $schedule->loadMissing('route.stops', 'vehicle');
        $tripIds = Trip::where('tripable_id', $schedule->id)
            ->where('tripable_type', PublicBusSchedule::class)
            ->whereDate('trip_datetime', $request->trip_date)
            ->pluck('id');
        $bookedSeats = Booking::whereIn('trip_id', $tripIds)
                              ->pluck('seat_number')
                              ->toArray();
        return response()->json([
            'status' => true,
            'code' => 'schedule_details_found',
            'message' => __('messages.schedule_details_found'),
            'data' => new ScheduleDetailsResource($schedule, $bookedSeats)
        ]);
    }

    public function searchPrivateHireDrivers(SearchPrivateHireRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tripStart = \Carbon\Carbon::parse($data['trip_datetime']);
        $tripEnd = $tripStart->copy()->addHours(2); // Assuming a 2-hour trip duration for conflict checking

        // Filter drivers based on the required seat count
        $seatCount = $data['seat_count'] ?? 1;

        $availableDrivers = Driver::where('availability_status', 'available')
            ->whereHas('user.vehicles', function ($query) use ($seatCount) {
                $query->where('is_active', true)
                      ->where('seat_count', '>=', $seatCount);
            })
            ->with(['user.vehicles' => function($q) use ($seatCount) {
                $q->where('seat_count', '>=', $seatCount)->with('amenities'); // Eager load amenities
            }, 'user'])
            ->get();

        // Additional filter to avoid schedule conflicts
        $drivers = $availableDrivers->filter(function ($driver) use ($tripStart, $tripEnd) {
            // Can add a schedule conflict check here later
            return true;
        });

        return AvailableDriverResource::collection($drivers)->response();
    }

    /**
    * Get the available seat counts for private buses
     */
    public function getPrivateBusSeatCounts(): JsonResponse
    {
        $seatCounts = Vehicle::where('is_active', true)
            ->whereHas('driver.driverProfile', function ($query) {
                $query->where('availability_status', 'available');
            })
            ->distinct()
            ->pluck('seat_count')
            ->sort()
            ->values();

        return response()->json([
            'status' => true,
            'code' => 'seat_counts_retrieved',
            'message' => __('messages.seat_counts_retrieved_successfully'),
            'data' => $seatCounts
        ]);
    }


    public function myTrips(Request $request)
    {
        $user = $request->user();
        $now = now();

        $trips = $user->tripsAsRider()
            ->with(['vehicle', 'driver', 'bookings', 'schoolSubscriptions.schoolPackage', 'ratings'])
            ->latest('trip_datetime')
            ->get()
            ->map(function($trip) use ($now, $user) {
                // Add the missing data
                $trip->created_at = $trip->created_at;
                $trip->fare = $trip->total_fare;
                $trip->payment_method = $trip->payment_method;
                $trip->payment_date = $trip->payment_status === 'paid' ? $trip->updated_at : null;
                $trip->distance = $trip->distance ?? null;
                $trip->duration = $trip->duration ?? null;

                // Add the addresses for the public bus
                if ($trip->type === 'public_bus') {
                    $trip->pickup_location = $trip->pickup_address ?? null;
                    $trip->dropoff_location = $trip->dropoff_address ?? null;
                }
                $trip->amenities = $trip->vehicle?->amenities?->pluck('name')->toArray() ?? [];
                $trip->responsible_person_name = $trip->responsible_person_name ?? null;
                $trip->responsible_person_id_photo_url = $trip->responsible_person_id_photo_url ?? null;
                $trip->return_datetime = $trip->return_datetime ?? null;
                if (in_array($trip->status, ['cancelled_by_rider', 'cancelled_by_driver', 'cancelled_by_admin'])) {
                    $trip->category = 'cancelled';
                } elseif ($trip->status == 'completed' || $trip->trip_datetime < $now->subHours(2)) {
                    $trip->category = 'completed';
                } elseif ($trip->trip_datetime > $now) {
                    $trip->category = 'upcoming';
                } else {
                    $trip->category = 'current';
                }

                $userRating = $trip->ratings->where('rater_id', $user->id)->first();
                $trip->is_rated_by_user = (bool)$userRating;

                // *** This is the new part that was added ***
                // Add the actual rating data if it exists
                $trip->user_rating_details = $userRating ? [
                    'rating' => $userRating->rating,
                    'comment' => $userRating->comment
                ] : null;
                // ********************************************

                return $trip;
            });

        $filteredTrips = $trips->filter(function($trip) {
            if ($trip->type === 'school_service' && $trip->tripable_type !== \App\Models\SchoolSubscription::class) {
                return false;
            }
            return true;
        });

        return response()->json([
            'status' => true,
            'code' => 'trips_found',
            'message' => 'Trips retrieved successfully.',
            'data' => $filteredTrips->values()
        ]);
    }

    // --- Rider specific functions ---
    public function cancel(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($trip->status !== 'pending' && $trip->status !== 'approved') {
            return response()->json(['message' => 'This trip cannot be cancelled at its current stage.'], 422);
        }
        if (now()->addHour() > $trip->trip_datetime) {
            return response()->json(['message' => 'Cancellation window has passed.'], 422);
        }
        $trip->status = 'cancelled_by_rider';
        $trip->save();
        if ($trip->payment_method === 'wallet' && $trip->payment_status === 'paid') {
            $request->user()->wallet()->increment('balance', $trip->total_fare);
            // Can add a transaction log here
        }
        return response()->json([
            'status' => true,
            'message' => 'Trip cancelled successfully.'
        ]);
    }

    public function rate(StoreRatingRequest $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($trip->status !== 'completed') {
            return response()->json(['message' => 'You can only rate completed trips.'], 422);
        }
        $existingRating = $trip->ratings()->where('rater_id', $request->user()->id)->exists();
        if ($existingRating) {
            return response()->json(['message' => 'You have already rated this trip.'], 422);
        }
        $trip->ratings()->create([
            'rater_id' => $request->user()->id,
            'rated_id' => $trip->driver_id,
            'rating' => $request->validated()['rating'],
            'comment' => $request->validated()['comment'] ?? null,
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Thank you for your feedback!'
        ]);
    }

    public function reportProblem(StoreProblemReportRequest $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $trip->problemReports()->create([
            'reporter_id' => $request->user()->id,
            'category' => $request->validated()['category'],
            'description' => $request->validated()['description'],
        ]);
        return response()->json([
            'status' => true,
            'message' => 'Problem reported successfully. We will contact you soon.'
        ]);
    }
}
