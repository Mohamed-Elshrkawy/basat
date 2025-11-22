<?php

namespace App\Http\Controllers\Api\Client\Booking;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Client\Booking\BookingDetailResource;
use App\Http\Resources\Api\Client\Booking\IndexBookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    /**
     * Get user bookings
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $status = $request->query('status');

        $bookings = Booking::with(['schedule.route.startCity', 'schedule.route.endCity'])
            ->where('user_id', Auth::id())
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return IndexBookingResource::collection($bookings)->additional([
            'status'=> 'success',
            'message'=> __('Bookings fetched successfully')
        ]);
    }

    /**
     * Get booking details
     */
    public function show(Booking $booking): JsonResponse
    {
        return json( new BookingDetailResource($booking));
    }

    /**
     * Cancel booking
     */
    public function cancel(Booking $booking, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (!$booking->canBeCancelled()) {
            return json(__('Booking can not be cancelled'), status: 'fail', headerStatus: 422);
        }

        DB::beginTransaction();

        try {
            if($booking->isPaid())
            {
                $desc = [
                    'ar' => 'استرداد مبلغ ' . $booking->total_amount . ' لحجز رقم ' . $booking->booking_number,
                    'en' => 'Refund of amount ' . $booking->total_amount . ' for booking number ' . $booking->booking_number,
                ];
                $booking->user->deposit((float)$booking->total_amount,$desc);
            }
            $booking->cancel($validated['reason'] ?? null);

            DB::commit();

            return json(new BookingDetailResource($booking->refresh()),__('Booking Cancelled'));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to cancel booking'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(Booking $booking, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', enabled_payment_methods_array()),
            'transaction_id' => 'sometimes|string',
        ]);

        if ($booking->isPaid()) {
            return json(__('Booking already paid'), status: 'fail', headerStatus: 422);
        }

        $transactionId = $validated['transaction_id'] ?? null;

        DB::beginTransaction();

        try {
            if($validated['payment_method'] == 'wallet')
            {
                $desc = [
                    'ar' => 'تم خصم مبلغ ' . $booking->total_amount . ' من حسابك لحجز رقم ' . $booking->booking_number,
                    'en' => 'An amount of ' . $booking->total_amount . ' has been deducted from your account for booking number ' . $booking->booking_number
                ];
                $booking->user->withdraw((float)$booking->total_amount, $desc);
            }
            $booking->markAsPaid($validated);

            DB::commit();

            return json(new BookingDetailResource($booking->refresh()),__('Booking Paid'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to confirm payment'), status: 'fail', headerStatus: 500);
        }
    }
}
