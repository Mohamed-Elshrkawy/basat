<?php

namespace App\Http\Controllers\Api\Client\BookingSeat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\BookingSeat\AvailableSeatsRequest;
use App\Http\Requests\Api\Client\BookingSeat\CreateBookingRequest;
use App\Http\Resources\Api\Client\BookingSeat\BookingDetailResource;
use App\Http\Resources\Api\Client\BookingSeat\BookingResource;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Get available seats for a schedule on a specific date
     */
    public function availableSeats(AvailableSeatsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $schedule = Schedule::with('route')->find($validated['schedule_id']);

        if (!$schedule || !$schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير متاحة',
            ], 404);
        }

        $bookedSeats = Booking::where('schedule_id', $validated['schedule_id'])
            ->where('travel_date', $validated['travel_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('seat_numbers')
            ->flatten()
            ->unique()
            ->values();

        $totalSeats = 50;
        $availableSeatsCount = $totalSeats - $bookedSeats->count();

        return response()->json([
            'success' => true,
            'data' => [
                'schedule_id' => $schedule->id,
                'travel_date' => $validated['travel_date'],
                'total_seats' => $totalSeats,
                'available_seats' => $availableSeatsCount,
                'booked_seats' => $bookedSeats,
            ],
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // التحقق من تطابق عدد المقاعد
        if (count($validated['seat_numbers']) !== $validated['number_of_seats']) {
            return response()->json([
                'success' => false,
                'message' => 'عدد المقاعد لا يتطابق مع أرقام المقاعد المختارة',
            ], 422);
        }

        // التحقق من عدم تكرار أرقام المقاعد
        if (count($validated['seat_numbers']) !== count(array_unique($validated['seat_numbers']))) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تكرار رقم المقعد',
            ], 422);
        }

        $schedule = Schedule::with('route')->find($validated['schedule_id']);

        if (!$schedule || !$schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير متاحة',
            ], 404);
        }

        // التحقق من نوع الرحلة
        if ($validated['trip_type'] === 'round_trip' && $schedule->trip_type !== 'round_trip') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الرحلة لا تدعم الذهاب والعودة',
            ], 422);
        }

        // التحقق من اليوم
        $travelDate = \Carbon\Carbon::parse($validated['travel_date']);
        $dayOfWeek = $travelDate->format('l');

        if (!in_array($dayOfWeek, $schedule->days_of_week ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير متاحة في هذا اليوم',
            ], 422);
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
                return response()->json([
                    'success' => false,
                    'message' => 'بعض المقاعد محجوزة بالفعل',
                    'conflicting_seats' => array_values($conflictingSeats),
                ], 422);
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

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الحجز بنجاح',
                'data' => new BookingResource($booking->load('schedule.route.startCity', 'schedule.route.endCity')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الحجز',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user bookings
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $bookings = Booking::with(['schedule.route.startCity', 'schedule.route.endCity'])
            ->where('user_id', Auth::id())
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'bookings' => BookingResource::collection($bookings),
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'last_page' => $bookings->lastPage(),
                    'per_page' => $bookings->perPage(),
                    'total' => $bookings->total(),
                ],
            ],
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
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingDetailResource($booking),
        ]);
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
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الحجز',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $booking->cancel($validated['reason'] ?? null);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الحجز بنجاح',
                'data' => new BookingResource($booking->load('schedule.route.startCity', 'schedule.route.endCity')),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الحجز',
            ], 500);
        }
    }

    /**
     * Confirm payment
     */
    public function confirmPayment(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'الحجز غير موجود',
            ], 404);
        }

        if ($booking->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'تم دفع هذا الحجز مسبقاً',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $booking->markAsPaid($validated['transaction_id']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد الدفع بنجاح',
                'data' => new BookingResource($booking->load('schedule.route.startCity', 'schedule.route.endCity')),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تأكيد الدفع',
            ], 500);
        }
    }
}
