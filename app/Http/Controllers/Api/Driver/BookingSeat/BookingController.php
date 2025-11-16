<?php

namespace App\Http\Controllers\Api\Driver\BookingSeat;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Driver\BookingSeat\TripDetailResource;
use App\Http\Resources\Api\Driver\BookingSeat\TripListResource;
use App\Models\TripInstance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TripDetailResource($trip),
        ]);
    }

    /**
     * Start trip
     */
    public function start(int $id): JsonResponse
    {
        $driver = request()->user();

        $trip = TripInstance::forDriver($driver->id)->find($id);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        if (!$trip->isScheduled()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن بدء رحلة غير مجدولة',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $trip->start();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم بدء الرحلة بنجاح',
                'data' => new TripDetailResource($trip->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء بدء الرحلة',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        if (!$trip->isInProgress()) {
            return response()->json([
                'success' => false,
                'message' => 'يمكن إتمام الرحلات الجارية فقط',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $trip->update([
                'driver_notes' => $validated['driver_notes'] ?? $trip->driver_notes
            ]);

            $trip->complete();

            // Mark all boarded passengers as completed
            $trip->bookings()
                ->where('passenger_status', 'boarded')
                ->update([
                    'passenger_status' => 'completed',
                    'arrived_at' => now(),
                    'status' => 'completed',
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إتمام الرحلة بنجاح',
                'data' => new TripDetailResource($trip->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إتمام الرحلة',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        $stationProgress = $trip->stationProgress()->find($stationProgressId);

        if (!$stationProgress) {
            return response()->json([
                'success' => false,
                'message' => 'المحطة غير موجودة',
            ], 404);
        }

        if (!$stationProgress->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'المحطة تم الوصول إليها بالفعل',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $stationProgress->update([
                'notes' => $validated['notes'] ?? $stationProgress->notes
            ]);

            $stationProgress->markArrived();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الوصول للمحطة',
                'data' => [
                    'station' => $stationProgress->station->city->getTranslation('name', 'ar'),
                    'arrived_at' => $stationProgress->arrived_at->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الوصول',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        $stationProgress = $trip->stationProgress()->find($stationProgressId);

        if (!$stationProgress) {
            return response()->json([
                'success' => false,
                'message' => 'المحطة غير موجودة',
            ], 404);
        }

        if (!$stationProgress->isArrived()) {
            return response()->json([
                'success' => false,
                'message' => 'يجب الوصول للمحطة أولاً',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $stationProgress->markDeparted();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المغادرة من المحطة',
                'data' => [
                    'station' => $stationProgress->station->city->getTranslation('name', 'ar'),
                    'departed_at' => $stationProgress->departed_at->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل المغادرة',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $booking->checkIn();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل حضور الراكب',
                'data' => [
                    'booking_number' => $booking->booking_number,
                    'passenger_name' => $booking->user->name,
                    'checked_in_at' => $booking->checked_in_at->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الحضور',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark passenger as boarded (صعد الباص)
     */
    public function boardPassenger(int $tripId, int $bookingId, Request $request): JsonResponse
    {
        $driver = request()->user();

        $validated = $request->validate([
            'schedule_stop_id' => 'nullable|exists:schedule_stops,id',
            'driver_notes' => 'nullable|string|max:500',
        ]);

        $trip = TripInstance::forDriver($driver->id)->find($tripId);

        if (!$trip) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'driver_notes' => $validated['driver_notes'] ?? $booking->driver_notes
            ]);

            $booking->board($validated['schedule_stop_id'] ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل صعود الراكب',
                'data' => [
                    'booking_number' => $booking->booking_number,
                    'passenger_name' => $booking->user->name,
                    'boarded_at' => $booking->boarded_at->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الصعود',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير موجودة',
            ], 404);
        }

        $booking = $trip->bookings()->find($bookingId);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $booking->markNoShow();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل عدم حضور الراكب',
                'data' => [
                    'booking_number' => $booking->booking_number,
                    'passenger_name' => $booking->user->name,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
