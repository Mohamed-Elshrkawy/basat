<?php

namespace App\Http\Controllers\Api\Driver\BookingSeat;

use App\Helpers\DriverEarningsHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Driver\BookingSeat\TripDetailResource;
use App\Http\Resources\Api\Driver\BookingSeat\TripListResource;
use App\Models\TripInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Get driver's trips for the week with filters
     */
    public function index(Request $request): JsonResponse
    {
        $driver = request()->user();

        $filter = $request->query('filter', 'all');

        $query = TripInstance::with([
            'schedule.route.startCity',
            'schedule.route.endCity'
        ])
            ->forDriver($driver->id)
            ->thisWeek()
            ->orderBy('trip_date', 'asc')
            ->orderBy('created_at', 'asc');

        // Apply filters
        match($filter) {
            'upcoming' => $query->upcoming(),
            'current' => $query->inProgress(),
            'completed' => $query->completed(),
            default => null
        };

        $trips = $query->get();

        return json(TripListResource::collection($trips), __('Trips fetched successfully'));
    }

    /**
     * Get trip details
     */
    public function show(int $id): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::with([
            'schedule.route.startCity',
            'schedule.route.endCity',
            'stationProgress.scheduleStop.stop'
        ])
            ->forDriver($driver->id)
            ->find($id);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        return json( new TripDetailResource($trip));
    }

    /**
     * Start trip
     */
    public function start(int $id): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::forDriver($driver->id)->find($id);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        if (!$trip->isScheduled()) {
            return json(__('Trip is not scheduled'), status: 'fail', headerStatus: 422);

        }

        DB::beginTransaction();

        try {
            $trip->start();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to start trip'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Complete trip
     */
    public function complete(int $id, Request $request): JsonResponse
    {
        $driver = request()->user();

        $validated = $request->validate([
            'driver_notes' => 'nullable|string|max:1000',
        ]);

        $trip = TripInstance::forDriver($driver->id)->find($id);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        if (!$trip->isInProgress()) {
            return json(__('Trip is not in progress'), status: 'fail', headerStatus: 422);

        }

        DB::beginTransaction();

        try {
            $trip->update([
                'driver_notes' => $validated['driver_notes'] ?? $trip->driver_notes
            ]);

            $trip->complete();

            // Mark all boarded passengers as completed
            $completedBookings = $trip->bookings()
                ->where('passenger_status', 'boarded')
                ->get();

            foreach ($completedBookings as $booking) {
                $booking->update([
                    'passenger_status' => 'completed',
                    'arrived_at' => now(),
                    'status' => 'completed',
                ]);

                // معالجة مستحقات السائق لكل حجز مكتمل
                try {
                    $earningsResult = DriverEarningsHelper::processDriverEarnings($booking);
                    Log::info('Driver earnings processed for booking #' . $booking->booking_number, $earningsResult);
                } catch (\Exception $e) {
                    Log::error('Failed to process driver earnings for booking #' . $booking->booking_number . ': ' . $e->getMessage());
                    // نكمل العملية حتى لو فشل حساب المستحقات
                }
            }

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();
            return json(__('Failed to complete trip'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Mark station as arrived
     */
    public function markStationArrived(int $tripId, int $stationProgressId, Request $request): JsonResponse
    {
        $driver = request()->user();

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        $stationProgress = $trip->stationProgress()->find($stationProgressId);

        if (!$stationProgress) {
            return json(__('Station Not Fond'), status: 'fail', headerStatus: 422);
        }

        if (!$stationProgress->isPending()) {
            return json(__('Station is not pending'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $stationProgress->update([
                'notes' => $validated['notes'] ?? $stationProgress->notes
            ]);

            $stationProgress->markArrived();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to mark station as arrived'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Mark station as departed
     */
    public function markStationDeparted(int $tripId, int $stationProgressId): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        $stationProgress = $trip->stationProgress()->find($stationProgressId);

        if (!$stationProgress) {
            return json(__('Station Not Fond'), status: 'fail', headerStatus: 422);
        }

        if (!$stationProgress->isArrived()) {
            return json(__('Station is not arrived'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $stationProgress->markDeparted();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to mark station as departed'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Mark passenger as checked in (حضر)
     */
    public function checkInPassenger(int $tripId, int $bookingId): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return json(__('Booking Not Fond'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->checkIn();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to mark passenger as checked in'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Mark passenger as boarded (صعد الباص)
     */
    public function boardPassenger(int $tripId, int $bookingId, Request $request): JsonResponse
    {
        $driver = request()->user();

        $validated = $request->validate([
            'driver_notes' => 'nullable|string|max:500',
        ]);

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return json(__('Booking Not Fond'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'driver_notes' => $validated['driver_notes'] ?? $booking->driver_notes,
                'boarded_at' => now(),
            ]);

            $booking->board();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to mark passenger as boarded'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Mark passenger as no show
     */
    public function markPassengerNoShow(int $tripId, int $bookingId): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return json(__('Trip Not Fond'), status: 'fail', headerStatus: 422);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return json(__('Booking Not Fond'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->markNoShow();

            DB::commit();

            return json(new TripDetailResource($trip->fresh()));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to mark passenger as no show'), status: 'fail', headerStatus: 500);
        }
    }
}
