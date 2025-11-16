<?php

namespace App\Http\Controllers\Api\Client\BookingSeat;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Route;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

        // Total seats (يمكن جعله متغير من جدول الباصات)
        $totalSeats = 50;

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
