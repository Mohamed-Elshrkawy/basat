<?php

namespace App\Http\Controllers\Api\Client\BookingSeat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\BookingSeat\CreateBookingRequest;
use App\Http\Resources\Api\Client\BookingSeat\BookingSammaryResource;
use App\Models\Schedule;
use App\Models\Route;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    /**
     * Search for available schedules between two cities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_city_id' => 'required|exists:cities,id',
            'to_city_id' => 'required|exists:cities,id',
            'trip_type' => 'required|in:one_way,round_trip',
            'start_date' => 'nullable|date|after_or_equal:today',
        ]);

        $fromCityId = $validated['from_city_id'];
        $toCityId = $validated['to_city_id'];
        $tripType = $validated['trip_type'];
        $startDate = $validated['start_date'] ?? now()->format('Y-m-d');

        // Get routes between the two cities
        $routes = Route::active()
            ->where(function ($query) use ($fromCityId, $toCityId) {
                $query->where('start_city_id', $fromCityId)
                    ->where('end_city_id', $toCityId);
            })
            ->get();

        if ($routes->isEmpty()) {
            return json(__('No routes found between the two cities'), status: 'fail', headerStatus: 422);
        }

        $routeIds = $routes->pluck('id');

        // Get schedules for the next 7 days
        $schedules = [];
        $startDate = Carbon::parse($startDate);

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dayOfWeek = $currentDate->format('l');

            $daySchedules = Schedule::with(['route.startCity', 'route.endCity', 'driver'])
                ->whereIn('route_id', $routeIds)
                ->where('is_active', true)
                ->where('available_seats', '>', 0)
                ->whereJsonContains('days_of_week', $dayOfWeek)
                ->when($tripType, function ($query) use ($tripType) {
                    $query->where('trip_type', $tripType);
                })
                ->get()
                ->map(function ($schedule) use ($currentDate) {
                    return $this->formatSchedule($schedule, $currentDate);
                });


            if ($daySchedules->isNotEmpty()) {
                $schedules[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'date_formatted' => $currentDate->translatedFormat('l, d F Y h:i A'),
                    'day_name' => $currentDate->locale('ar')->dayName,
                    'trips' => $daySchedules,
                ];
            }
        }

        return json($schedules, __('Trips fetched successfully'));
    }

    /**
     * Get schedule details by ID
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'travel_date' => 'required|date|after_or_equal:today',
        ]);

        $schedule = Schedule::with([
            'route.startCity',
            'route.endCity',
            'driver',
            'scheduleStops.stop'
        ])
            ->where('is_active', true)
            ->find($id);

        if (!$schedule) {
            return json(__('Schedule not found'), status: 'fail', headerStatus: 422);
        }

        // التحقق من أن الرحلة تعمل في هذا اليوم
        $travelDate = Carbon::parse($validated['travel_date']);
        $dayOfWeek = $travelDate->format('l');

        if (!in_array($dayOfWeek, $schedule->days_of_week ?? [])) {
            return json(__('Schedule not found'), status: 'fail', headerStatus: 422);
        }

        // Get seats status for the travel date
        $seatsStatus = $this->getSeatsStatus($schedule->id, $validated['travel_date']);

        return json([
            'tripe' => $this->formatScheduleDetails($schedule),
            'seats' => $seatsStatus
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();

        if(!setting('enable_seat_booking'))
        {
            return json(__('Seat booking is disabled'), status: 'fail', headerStatus: 422);
        }

        // التحقق من تطابق عدد المقاعد
        if (count($validated['seat_numbers']) != $validated['number_of_seats']) {
            return json(__('Number of seats does not match selected seat numbers'), status: 'fail', headerStatus: 422);
        }

        // التحقق من عدم تكرار أرقام المقاعد
        if (count($validated['seat_numbers']) != count(array_unique($validated['seat_numbers']))) {
            return json(__('Seat numbers cannot be repeated'), status: 'fail', headerStatus: 422);
        }

        $schedule = Schedule::with('route', 'scheduleStops')->find($validated['schedule_id']);

        if (!$schedule || !$schedule->is_active) {
            return json(__('Schedule not found'), status: 'fail', headerStatus: 422);
        }

        // التحقق من نوع الرحلة
        if ($validated['trip_type'] === 'round_trip' && $schedule->trip_type !== 'round_trip') {
            return json(__('This schedule does not support round trip'), status: 'fail', headerStatus: 422);
        }

        // التحقق من المحطات للذهاب
        $outboundBoardingStop = $schedule->scheduleStops()
            ->where('id', $validated['outbound_boarding_stop_id'])
            ->where('direction', 'outbound')
            ->first();

        $outboundDroppingStop = $schedule->scheduleStops()
            ->where('id', $validated['outbound_dropping_stop_id'])
            ->where('direction', 'outbound')
            ->first();

        if (!$outboundBoardingStop || !$outboundDroppingStop) {
            return json(__('Invalid outbound stops for this schedule'), status: 'fail', headerStatus: 422);
        }

        // التحقق من ترتيب المحطات (المحطة التي سيركب منها يجب أن تكون قبل المحطة التي سينزل فيها)
        if ($outboundBoardingStop->order >= $outboundDroppingStop->order) {
            return json(__('Boarding stop must be before dropping stop'), status: 'fail', headerStatus: 422);
        }

        // التحقق من المحطات للعودة إذا كانت الرحلة ذهاب وعودة
        if ($validated['trip_type'] === 'round_trip') {
            $returnBoardingStop = $schedule->scheduleStops()
                ->where('id', $validated['return_boarding_stop_id'])
                ->where('direction', 'return')
                ->first();

            $returnDroppingStop = $schedule->scheduleStops()
                ->where('id', $validated['return_dropping_stop_id'])
                ->where('direction', 'return')
                ->first();

            if (!$returnBoardingStop || !$returnDroppingStop) {
                return json(__('Invalid return stops for this schedule'), status: 'fail', headerStatus: 422);
            }

            if ($returnBoardingStop->order >= $returnDroppingStop->order) {
                return json(__('Boarding stop must be before dropping stop'), status: 'fail', headerStatus: 422);
            }
        }

        // التحقق من اليوم
        $travelDate = \Carbon\Carbon::parse($validated['travel_date']);
        $dayOfWeek = $travelDate->format('l');

        if (!in_array($dayOfWeek, $schedule->days_of_week ?? [])) {
            return json(__('Schedule not available on this day'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            // التحقق من توفر المقاعد
            $bookedSeats = Booking::where('schedule_id', $validated['schedule_id'])
                ->where('travel_date', $validated['travel_date'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->lockForUpdate()
                ->pluck('seat_numbers')
                ->flatten()
                ->unique()
                ->toArray();

            $conflictingSeats = array_intersect($bookedSeats, $validated['seat_numbers']);

            if (!empty($conflictingSeats)) {
                DB::rollBack();
                return json(__('Some seats are already booked'), status: 'fail', headerStatus: 422);
            }

            // حساب السعر
            $outboundFare = $schedule->fare * $validated['number_of_seats'];
            $returnFare = 0;
            $discount = 0;

            if ($validated['trip_type'] === 'round_trip') {
                $returnFare = $schedule->return_fare * $validated['number_of_seats'];
                $discount = ($schedule->round_trip_discount ?? 0) * $validated['number_of_seats'];
            }

            $totalAmount = $outboundFare + $returnFare - $discount;

            // إنشاء الحجز
            $booking = Booking::create([
                'type' => 'public_bus',
                'user_id' => $user->id,
                'schedule_id' => $validated['schedule_id'],
                'driver_id' => $schedule?->driver_id,
                'travel_date' => $validated['travel_date'],
                'trip_type' => $validated['trip_type'],
                'number_of_seats' => $validated['number_of_seats'],
                'seat_numbers' => $validated['seat_numbers'],
                'outbound_boarding_stop_id' => $validated['outbound_boarding_stop_id'],
                'outbound_dropping_stop_id' => $validated['outbound_dropping_stop_id'],
                'return_boarding_stop_id' => $validated['return_boarding_stop_id'] ?? null,
                'return_dropping_stop_id' => $validated['return_dropping_stop_id'] ?? null,
                'outbound_fare' => $outboundFare,
                'return_fare' => $returnFare,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return json(
                new BookingSammaryResource($booking->load([
                    'schedule.route.startCity',
                    'schedule.route.endCity',
                    'outboundBoardingStop.stop',
                    'outboundDroppingStop.stop',
                    'returnBoardingStop.stop',
                    'returnDroppingStop.stop'
                ])),
                __('Booking created successfully')
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);

            return json(__('Failed to create booking'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Get seats status for a schedule on a specific date
     *
     * @param int $scheduleId
     * @param string $travelDate
     * @return array
     */

    private function getSeatsStatus(int $scheduleId, string $travelDate): array
    {
        // Get all booked seats for this schedule on this date
        $bookedSeats = Booking::where('schedule_id', $scheduleId)
            ->where('travel_date', $travelDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('seat_numbers')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        // Total seats from schedule
        $totalSeats = $schedule->available_seats ?? 50;

        // Build seats array
        $seats = [];
        for ($i = 1; $i <= $totalSeats; $i++) {
            $seats[] = [
                'seat_number' => $i,
                'is_available' => !in_array($i, $bookedSeats),
                'status' => in_array($i, $bookedSeats) ? 'booked' : 'available',
            ];
        }

        return [
            'total_seats' => $totalSeats,
            'available_seats' => $totalSeats - count($bookedSeats),
            'booked_seats_count' => count($bookedSeats),
            'seats' => $seats,
        ];
    }

    /**
     * Format schedule data
     *
     * @param Schedule $schedule
     * @param Carbon $date
     * @return array
     */
    private function formatSchedule(Schedule $schedule, Carbon $date): array
    {
        $departureTime = Carbon::parse($schedule->departure_time)->format('H:i:s');
        $arrivalTime = Carbon::parse($schedule->arrival_time)->format('H:i:s');

        $departureDateTime = Carbon::parse("{$date->format('Y-m-d')} {$departureTime}");
        $arrivalDateTime = Carbon::parse("{$date->format('Y-m-d')} {$arrivalTime}");

        $data = [
            'id' => $schedule->id,
            'route' => [
                'id' => $schedule->route->id,
                'name' => $schedule->route->getFullRouteName(),
                'from' => $schedule->route->startCity->getTranslation('name', 'ar'),
                'to' => $schedule->route->endCity->getTranslation('name', 'ar'),
            ],
            'trip_type' => $schedule->trip_type,
            'trip_type_label' => $schedule->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'outbound' => [
                'departure_time' => $schedule->departure_time,
                'departure_datetime' => $departureDateTime->format('h:i A'),
                'arrival_time' => $schedule->arrival_time,
                'arrival_datetime' => $arrivalDateTime->format('h:i A'),
                'duration' => $this->calculateDuration($schedule->departure_time, $schedule->arrival_time),
                'fare' => (float) $schedule->fare,
            ],
            'available_seats' => $schedule->available_seats,
            'driver' => $schedule->driver ? [
                'id' => $schedule->driver->id,
                'name' => $schedule->driver->name,
            ] : null,
        ];

        // Add return trip info if round trip
        if ($schedule->trip_type === 'round_trip') {
            $returnDepartureTime = Carbon::parse($schedule->return_departure_time)->format('H:i:s');
            $returnArrivalTime = Carbon::parse($schedule->return_arrival_time)->format('H:i:s');

            $returnDepartureDateTime = Carbon::parse("{$date->format('Y-m-d')} {$returnDepartureTime}");
            $returnArrivalDateTime = Carbon::parse("{$date->format('Y-m-d')} {$returnArrivalTime}");

            $data['return'] = [
                'departure_time' => $schedule->return_departure_time,
                'departure_datetime' => $returnDepartureDateTime->format('h:i A'),
                'arrival_time' => $schedule->return_arrival_time,
                'arrival_datetime' => $returnArrivalDateTime->format('h:i A'),
                'duration' => $this->calculateDuration($schedule->return_departure_time, $schedule->return_arrival_time),
                'fare' => (float) $schedule->return_fare,
            ];

            $data['pricing'] = [
                'outbound_fare' => (float) $schedule->fare,
                'return_fare' => (float) $schedule->return_fare,
                'total' => (float) ($schedule->fare + $schedule->return_fare),
                'discount' => (float) ($schedule->round_trip_discount ?? 0),
                'final_total' => (float) ($schedule->fare + $schedule->return_fare - ($schedule->round_trip_discount ?? 0)),
            ];
        }

        return $data;
    }

    /**
     * Format schedule details with stops
     *
     * @param Schedule $schedule
     * @return array
     */
    private function formatScheduleDetails(Schedule $schedule): array
    {
        $data = [
            'id' => $schedule->id,
            'route' => [
                'id' => $schedule->route->id,
                'name' => $schedule->route->getFullRouteName(),
                'from' => $schedule->route->startCity->getTranslation('name', 'ar'),
                'to' => $schedule->route->endCity->getTranslation('name', 'ar'),
            ],
            'trip_type' => $schedule->trip_type,
            'trip_type_label' => $schedule->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'outbound' => [
                'departure_time' => $schedule->departure_time,
                'arrival_time' => $schedule->arrival_time,
                'duration' => $this->calculateDuration($schedule->departure_time, $schedule->arrival_time),
                'fare' => (float) $schedule->fare,
                'stops' => $schedule->scheduleStops()
                    ->where('direction', 'outbound')
                    ->orderBy('order')
                    ->get()
                    ->map(function ($stop) {
                        return [
                            'id' => $stop->id,
                            'stop_name' => $stop->stop->getTranslation('name', 'ar'),
                            'arrival_time' => $stop->arrival_time,
                            'departure_time' => $stop->departure_time,
                            'order' => $stop->order,
                        ];
                    }),
            ],
            'available_seats' => $schedule->available_seats,
            'days_of_week' => $schedule->days_of_week,
            'driver' => $schedule->driver ? [
                'id' => $schedule->driver->id,
                'name' => $schedule->driver->name,
            ] : null,
        ];

        // Add return trip info if round trip
        if ($schedule->trip_type === 'round_trip') {
            $data['return'] = [
                'departure_time' => $schedule->return_departure_time,
                'arrival_time' => $schedule->return_arrival_time,
                'duration' => $this->calculateDuration($schedule->return_departure_time, $schedule->return_arrival_time),
                'fare' => (float) $schedule->return_fare,
                'stops' => $schedule->scheduleStops()
                    ->where('direction', 'return')
                    ->orderBy('order')
                    ->get()
                    ->map(function ($stop) {
                        return [
                            'id' => $stop->id,
                            'stop_name' => $stop->stop->getTranslation('name', 'ar'),
                            'arrival_time' => $stop->arrival_time,
                            'departure_time' => $stop->departure_time,
                            'order' => $stop->order,
                        ];
                    }),
            ];

            $data['pricing'] = [
                'outbound_fare' => (float) $schedule->fare,
                'return_fare' => (float) $schedule->return_fare,
                'total' => (float) ($schedule->fare + $schedule->return_fare),
                'discount' => (float) ($schedule->round_trip_discount ?? 0),
                'final_total' => (float) ($schedule->fare + $schedule->return_fare - ($schedule->round_trip_discount ?? 0)),
            ];
        }

        return $data;
    }

    /**
     * Calculate duration between two times
     *
     * @param string $startTime
     * @param string $endTime
     * @return string
     */
    private function calculateDuration(string $startTime, string $endTime): string
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $diffInMinutes = $start->diffInMinutes($end);
        $hours = floor($diffInMinutes / 60);
        $minutes = $diffInMinutes % 60;

        if ($hours > 0) {
            return sprintf('%d ساعة و %d دقيقة', $hours, $minutes);
        }

        return sprintf('%d دقيقة', $minutes);
    }
}
