<?php

namespace App\Http\Controllers\Api\Client\BookingSeat;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    /**
     * Get available seats for a schedule on a specific date
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableSeats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date|after_or_equal:today',
        ]);

        $schedule = Schedule::with('route')->find($validated['schedule_id']);

        if (!$schedule || !$schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الرحلة غير متاحة',
            ], 404);
        }

        // Get booked seats for this schedule on this date
        $bookedSeats = Booking::where('schedule_id', $validated['schedule_id'])
            ->where('travel_date', $validated['travel_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('seat_numbers')
            ->flatten()
            ->unique()
            ->values();

        $totalSeats = 50; // يمكن جعله متغير في جدول الباصات
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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'travel_date' => 'required|date|after_or_equal:today',
            'trip_type' => 'required|in:one_way,round_trip',
            'number_of_seats' => 'required|integer|min:1|max:10',
            'seat_numbers' => 'required|array|min:1|max:10',
            'seat_numbers.*' => 'required|integer|min:1|max:50',
            'payment_method' => 'required|in:cash,card,wallet,bank_transfer',
            'notes' => 'nullable|string|max:500',
        ]);

        // التحقق من تطابق عدد المقاعد مع أرقام المقاعد
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

            $requestedSeats = $validated['seat_numbers'];
            $conflictingSeats = array_intersect($bookedSeats, $requestedSeats);

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

            // تقليل عدد المقاعد المتاحة
            // $schedule->decrement('available_seats', $validated['number_of_seats']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الحجز بنجاح',
                'data' => $this->formatBooking($booking->load('schedule.route')),
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
     *
     * @param Request $request
     * @return JsonResponse
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
                'bookings' => $bookings->map(fn($booking) => $this->formatBooking($booking)),
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
     *
     * @param int $id
     * @return JsonResponse
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
            'data' => $this->formatBookingDetails($booking),
        ]);
    }

    /**
     * Cancel booking
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
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
                'data' => $this->formatBooking($booking->load('schedule.route')),
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
     * Confirm payment (webhook or manual)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
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
                'data' => $this->formatBooking($booking->load('schedule.route')),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تأكيد الدفع',
            ], 500);
        }
    }

    /**
     * Format booking data
     *
     * @param Booking $booking
     * @return array
     */
    private function formatBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'route' => [
                'from' => $booking->schedule->route->startCity->getTranslation('name', 'ar'),
                'to' => $booking->schedule->route->endCity->getTranslation('name', 'ar'),
            ],
            'travel_date' => $booking->travel_date->format('Y-m-d'),
            'travel_date_formatted' => $booking->travel_date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
            'trip_type' => $booking->trip_type,
            'trip_type_label' => $booking->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'number_of_seats' => $booking->number_of_seats,
            'seat_numbers' => $booking->seat_numbers,
            'total_amount' => (float) $booking->total_amount,
            'payment_method' => $booking->payment_method,
            'payment_status' => $booking->payment_status,
            'status' => $booking->status,
            'status_label' => $this->getStatusLabel($booking->status),
            'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format booking details
     *
     * @param Booking $booking
     * @return array
     */
    private function formatBookingDetails(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'schedule' => [
                'id' => $booking->schedule->id,
                'route' => [
                    'from' => $booking->schedule->route->startCity->getTranslation('name', 'ar'),
                    'to' => $booking->schedule->route->endCity->getTranslation('name', 'ar'),
                ],
                'departure_time' => $booking->schedule->departure_time,
                'arrival_time' => $booking->schedule->arrival_time,
                'return_departure_time' => $booking->schedule->return_departure_time,
                'return_arrival_time' => $booking->schedule->return_arrival_time,
                'driver' => $booking->schedule->driver ? [
                    'name' => $booking->schedule->driver->name,
                ] : null,
            ],
            'travel_date' => $booking->travel_date->format('Y-m-d'),
            'travel_date_formatted' => $booking->travel_date->locale('ar')->isoFormat('dddd، D MMMM YYYY'),
            'trip_type' => $booking->trip_type,
            'trip_type_label' => $booking->trip_type === 'one_way' ? 'ذهاب فقط' : 'ذهاب وعودة',
            'number_of_seats' => $booking->number_of_seats,
            'seat_numbers' => $booking->seat_numbers,
            'pricing' => [
                'outbound_fare' => (float) $booking->outbound_fare,
                'return_fare' => (float) $booking->return_fare,
                'discount' => (float) $booking->discount,
                'total_amount' => (float) $booking->total_amount,
            ],
            'payment' => [
                'method' => $booking->payment_method,
                'status' => $booking->payment_status,
                'transaction_id' => $booking->transaction_id,
                'paid_at' => $booking->paid_at?->format('Y-m-d H:i:s'),
            ],
            'status' => $booking->status,
            'status_label' => $this->getStatusLabel($booking->status),
            'notes' => $booking->notes,
            'cancellation' => [
                'reason' => $booking->cancellation_reason,
                'cancelled_at' => $booking->cancelled_at?->format('Y-m-d H:i:s'),
            ],
            'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get status label in Arabic
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            'pending' => 'في انتظار الدفع',
            'confirmed' => 'مؤكد',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            'refunded' => 'تم الاسترداد',
            default => $status,
        };
    }
}
