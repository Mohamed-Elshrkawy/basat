<?php

namespace App\Http\Controllers\Api\Driver\BookingPrivateBus;

use App\Helpers\DriverEarningsHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingPrivateBusController extends Controller
{
    /**
     * Get driver's private bus bookings
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,confirmed,in_progress,completed,cancelled',
            'trip_status' => 'nullable|in:pending,on_way_to_pickup,arrived_at_pickup,in_progress,completed',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $driver = request()->user();

        $bookings = Booking::with([
            'user',
            'vehicle',
            'startCity',
            'endCity',
            'amenities'
        ])
            ->where('type', 'private_bus')
            ->where('driver_id', $driver->id)
            ->when($validated['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($validated['trip_status'] ?? null, function ($query, $tripStatus) {
                $query->where('trip_status', $tripStatus);
            })
            ->when($validated['date_from'] ?? null, function ($query, $dateFrom) {
                $query->whereDate('travel_date', '>=', $dateFrom);
            })
            ->when($validated['date_to'] ?? null, function ($query, $dateTo) {
                $query->whereDate('travel_date', '<=', $dateTo);
            })
            ->orderBy('travel_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return json($bookings, __('Bookings fetched successfully'));
    }

    /**
     * Get booking details
     */
    public function show(Booking $booking): JsonResponse
    {
        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        // التحقق من أن الحجز من نوع private_bus
        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        $booking->load([
            'user',
            'vehicle.brand',
            'vehicle.vehicleModel',
            'startCity',
            'endCity',
            'amenities'
        ]);

        return json($booking, __('Booking details fetched successfully'));
    }

    /**
     * Accept booking
     */
    public function accept(Booking $booking, Request $request): JsonResponse
    {
        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if ($booking->status !== 'pending') {
            return json(__('Booking cannot be accepted'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'confirmed',
                'trip_status' => 'pending',
            ]);

            DB::commit();

            return json($booking->refresh(), __('Booking accepted successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to accept booking'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Reject booking
     */
    public function reject(Booking $booking, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if ($booking->status !== 'pending') {
            return json(__('Booking cannot be rejected'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            // إرجاع المبلغ إذا كان مدفوع
            if ($booking->isPaid()) {
                $desc = [
                    'ar' => 'استرداد مبلغ ' . $booking->total_amount . ' لحجز رقم ' . $booking->booking_number . ' (تم رفضه من قبل السائق)',
                    'en' => 'Refund of amount ' . $booking->total_amount . ' for booking number ' . $booking->booking_number . ' (rejected by driver)',
                ];
                $booking->user->deposit((float)$booking->total_amount, $desc);
            }

            $booking->cancel($validated['reason']);

            DB::commit();

            return json($booking->refresh(), __('Booking rejected successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to reject booking'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Update trip status
     */
    public function updateTripStatus(Booking $booking, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'trip_status' => 'required|in:on_way_to_pickup,arrived_at_pickup,in_progress,completed',
        ]);

        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if ($booking->status !== 'confirmed' && $booking->status !== 'in_progress') {
            return json(__('Cannot update trip status'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $updateData = [
                'trip_status' => $validated['trip_status'],
            ];

            // إذا كانت الحالة "in_progress"، نحدث status الحجز أيضاً
            if ($validated['trip_status'] === 'in_progress') {
                $updateData['status'] = 'in_progress';
            }

            // إذا كانت الحالة "completed"، نحدث status الحجز أيضاً
            if ($validated['trip_status'] === 'completed') {
                $updateData['status'] = 'completed';
            }

            $booking->update($updateData);

            DB::commit();

            return json($booking->refresh(), __('Trip status updated successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to update trip status'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Start trip
     */
    public function startTrip(Booking $booking, Request $request): JsonResponse
    {
        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if ($booking->status !== 'confirmed') {
            return json(__('Cannot start trip'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'in_progress',
                'trip_status' => 'in_progress',
            ]);

            DB::commit();

            return json($booking->refresh(), __('Trip started successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to start trip'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Complete trip
     */
    public function completeTrip(Booking $booking, Request $request): JsonResponse
    {
        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if ($booking->status !== 'in_progress') {
            return json(__('Cannot complete trip'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            $booking->update([
                'status' => 'completed',
                'trip_status' => 'completed',
            ]);

            // معالجة مستحقات السائق
            try {
                $earningsResult = DriverEarningsHelper::processDriverEarnings($booking);
                Log::info('Driver earnings processed for private booking #' . $booking->booking_number, $earningsResult);
            } catch (\Exception $e) {
                // إذا فشل حساب المستحقات، نسجل الخطأ لكن نكمل إكمال الرحلة
                Log::error('Failed to process driver earnings for booking #' . $booking->booking_number . ': ' . $e->getMessage());
                DB::rollBack();
                return json(__('Failed to process driver earnings: ') . $e->getMessage(), status: 'fail', headerStatus: 500);
            }

            DB::commit();

            return json($booking->refresh(), __('Trip completed successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to complete trip'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Cancel booking (by driver)
     */
    public function cancel(Booking $booking, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $driver = request()->user();

        // التحقق من أن الحجز يخص هذا السائق
        if ($booking->driver_id !== $driver->id) {
            return json(__('Unauthorized'), status: 'fail', headerStatus: 403);
        }

        if ($booking->type !== 'private_bus') {
            return json(__('Invalid booking type'), status: 'fail', headerStatus: 422);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return json(__('Cannot cancel this booking'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            // إرجاع المبلغ إذا كان مدفوع
            if ($booking->isPaid()) {
                $desc = [
                    'ar' => 'استرداد مبلغ ' . $booking->total_amount . ' لحجز رقم ' . $booking->booking_number . ' (تم إلغاؤه من قبل السائق)',
                    'en' => 'Refund of amount ' . $booking->total_amount . ' for booking number ' . $booking->booking_number . ' (cancelled by driver)',
                ];
                $booking->user->deposit((float)$booking->total_amount, $desc);
            }

            $booking->cancel($validated['reason']);

            DB::commit();

            return json($booking->refresh(), __('Booking cancelled successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to cancel booking'), status: 'fail', headerStatus: 500);
        }
    }
}
