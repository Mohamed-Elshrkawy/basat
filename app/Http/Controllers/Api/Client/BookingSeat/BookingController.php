<?php

namespace App\Http\Controllers\Api\Client\BookingSeat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\BookingSeat\AvailableSeatsRequest;
use App\Http\Requests\Api\Client\BookingSeat\CreateBookingRequest;
use App\Http\Resources\Api\Client\BookingSeat\BookingDetailResource;
use App\Http\Resources\Api\Client\BookingSeat\BookingResource;
use App\Http\Resources\Api\Client\BookingSeat\BookingSammaryResource;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{

    /**
     * Create a new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if(!setting('enable_seat_booking'))
        {
            return json(__('Seat booking is disabled'), status: 'fail', headerStatus: 422);
        }

        if(!in_array($validated['payment_method'], enabled_payment_methods_array()))
        {
            return json(__('Payment method is not enabled'), status: 'fail', headerStatus: 422);
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
                'user_id' => Auth::id(),
                'schedule_id' => $validated['schedule_id'],
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
                'payment_method' => $validated['payment_method'],
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

        return BookingResource::collection($bookings)->additional([
            'status'=> 'success',
            'message'=> __('Bookings fetched successfully')
        ]);
    }

    /**
     * Get booking details
     */
    public function show(int $id): JsonResponse
    {
        $booking = Booking::with(['schedule.route.startCity', 'schedule.route.endCity', 'schedule.driver'])
            ->where('user_id', Auth::id())
            ->find($id);

        if (!$booking) {
           return json(__('Booking not found'), status: 'fail', headerStatus: 422);
        }

        return json( new BookingDetailResource($booking));
    }

    /**
     * Cancel booking
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $booking = Booking::where('user_id', Auth::id())->find($id);

        if (!$booking) {
            return json(__('Booking not found'), status: 'fail', headerStatus: 422);
        }

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

            return json(new BookingResource($booking->load('schedule.route.startCity', 'schedule.route.endCity')),__('Booking Cancelled'));

        } catch (\Exception $e) {
            DB::rollBack();

            return json(__('Failed to cancel booking'), status: 'fail', headerStatus: 500);
        }
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:' . implode(',', enabled_payment_methods_array()),
            'transaction_id' => 'sometimes|string',
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return json(__('Booking not found'), status: 'fail', headerStatus: 422);
        }

        if ($booking->isPaid()) {
            return json(__('Booking already paid'), status: 'fail', headerStatus: 422);
        }

        $transactionId = $validated['transaction_id'] ?? null;

        DB::beginTransaction();

        try {
            if($booking->payment_method == 'wallet')
            {
                $desc = [
                    'ar' => 'تم خصم مبلغ ' . $booking->total_amount . ' من حسابك لحجز رقم ' . $booking->booking_number,
                    'en' => 'An amount of ' . $booking->total_amount . ' has been deducted from your account for booking number ' . $booking->booking_number
                ];
                $booking->user->withdraw((float)$booking->total_amount, $desc);
            }
            $booking->markAsPaid($transactionId);

            DB::commit();

            return json(new BookingResource($booking->load('schedule.route.startCity', 'schedule.route.endCity')),__('Booking Paid'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return json(__('Failed to confirm payment'), status: 'fail', headerStatus: 500);
        }
    }
}
