<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\UpdateLocationRequest;
use App\Http\Requests\Api\V1\Driver\UpdateStatusRequest;
use App\Http\Requests\Api\V1\Driver\UpdateStudentStatusRequest;
use App\Http\Requests\Api\V1\StoreProblemReportRequest;
use App\Http\Resources\Api\V1\Driver\TripResource;
use App\Http\Resources\Api\V1\Driver\TripDetailsResource;
use App\Models\Trip;
use App\Models\Booking;
use App\Models\SchoolSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Http\Requests\Api\V1\Driver\CollectWalletPaymentRequest;
use App\Models\User;
use App\Models\Notification;
use App\Notifications\GeneralNotification;

class DriverController extends Controller
{
    public function updateStatus(UpdateStatusRequest $request)
    {
        $driverProfile = $request->user()->driverProfile;
        if (!$driverProfile) {
            return response()->json([
                'status' => false,
                'code' => 'driver_profile_not_found',
                'message' => __('messages.driver_profile_not_found'),
                'data' => null
            ], 404);
        }
        $driverProfile->update(['availability_status' => $request->validated()['status']]);
        return response()->json([
            'status' => true,
            'code' => 'status_updated_successfully',
            'message' => __('messages.status_updated_successfully'),
            'data' => null
        ]);
    }

    public function updateLocation(UpdateLocationRequest $request)
    {
        $driverProfile = $request->user()->driverProfile;
        if (!$driverProfile) {
            return response()->json([
                'status' => false,
                'code' => 'driver_profile_not_found',
                'message' => __('messages.driver_profile_not_found'),
                'data' => null
            ], 404);
        }
        $driverProfile->update([
            'current_lat' => $request->validated()['lat'],
            'current_lng' => $request->validated()['lng'],
        ]);
        return response()->json([
            'status' => true,
            'code' => 'location_updated_successfully',
            'message' => __('messages.location_updated_successfully'),
            'data' => null
        ]);
    }

    public function getTrips(Request $request)
    {
        $request->validate([
            'status' => ['sometimes', 'in:pending,approved,on_way,completed,cancelled_by_rider,cancelled_by_driver']
        ]);
        $trips = $request->user()->tripsAsDriver()
            ->with('rider')
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest('trip_datetime')
            ->paginate(15);
        return TripResource::collection($trips);
    }

    public function getTripDetails(Trip $trip)
    {
        if (request()->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized_not_trip_driver')], 403);
        }

        $trip->load([
            'rider',
            'vehicle',
            'bookings.rider',
            'schoolSubscriptions.child.parent'
        ]);
        
        return new TripDetailsResource($trip);
    }

    public function updateTripStatus(Request $request, Trip $trip)
    {
        if ($request->user()->id !== $trip->driver_id) {
            return response()->json([
                'status' => false,
                'code' => 'unauthorized',
                'message' => 'Unauthorized',
                'data' => null
            ], 403);
        }
        $validated = $request->validate([
            'status' => ['required', Rule::in(['on_way', 'completed', 'cancelled_by_driver'])]
        ]);
        $oldStatus = $trip->status;
        $newStatus = $validated['status'];
        if ($oldStatus === $newStatus) {
            return new TripResource($trip);
        }
        $trip->status = $newStatus;
        $trip->save();

        // Add driver earning to wallet only if trip is completed for the first time
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            if ($trip->driver_earning > 0 && $trip->driver && $trip->driver->wallet) {
                $driverWallet = $trip->driver->wallet;
                $driverWallet->increment('balance', $trip->driver_earning);
                $driverWallet->transactions()->create([
                    'amount' => $trip->driver_earning,
                    'type' => 'payment',
                    'description' => ['en' => "Earning from Trip #{$trip->id}", 'ar' => "أرباح من رحلة رقم #{$trip->id}"],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);
                $trip->driver->notify(new GeneralNotification(
                    title: ['en' => 'New Earning', 'ar' => 'أرباح جديدة'],
                    body: ['en' => "You have earned {$trip->driver_earning} SAR from trip #{$trip->id}.", 'ar' => "لقد ربحت {$trip->driver_earning} ريال من الرحلة رقم #{$trip->id}."],
                    data: [
                        'type' => 'new_earning',
                        'trip_id' => $trip->id,
                        'amount' => $trip->driver_earning,
                    ]
                ));
            }
            if ($trip->rider) {
                $trip->rider->notify(new GeneralNotification(
                    title: ['en' => 'Trip Completed', 'ar' => 'الرحلة اكتملت'],
                    body: ['en' => "Trip #{$trip->id} has been completed. Thank you!", 'ar' => "اكتملت الرحلة رقم #{$trip->id}. شكراً لاستخدامكم خدماتنا!"],
                    data: [
                        'type' => 'trip_completed',
                        'trip_id' => $trip->id,
                    ]
                ));
            }
        }

        // Send notification to rider based on status change
        if ($trip->rider) {
            $notificationData = null;
            if ($newStatus === 'on_way' && $oldStatus !== 'on_way') {
                $notificationData = [
                    'title' => ['en' => 'Trip Started', 'ar' => 'الرحلة بدأت'],
                    'body' => ['en' => "Your driver is on the way for trip #{$trip->id}.", 'ar' => "السائق في الطريق إليك للرحلة رقم #{$trip->id}."],
                    'type' => 'trip_started'
                ];
            } elseif ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $notificationData = [
                    'title' => ['en' => 'Trip Completed', 'ar' => 'الرحلة اكتملت'],
                    'body' => ['en' => "Trip #{$trip->id} has been completed. Thank you!", 'ar' => "اكتملت الرحلة رقم #{$trip->id}. شكراً لاستخدامكم خدماتنا!"],
                    'type' => 'trip_completed'
                ];
            } elseif ($newStatus === 'cancelled_by_driver') {
                $notificationData = [
                    'title' => ['en' => 'Trip Cancelled', 'ar' => 'تم إلغاء الرحلة'],
                    'body' => ['en' => "We're sorry, trip #{$trip->id} has been cancelled by the driver.", 'ar' => "نعتذر، تم إلغاء الرحلة رقم #{$trip->id} من قبل السائق."],
                    'type' => 'trip_cancelled_by_driver'
                ];
            }
            if ($notificationData) {
                $trip->rider->notify(new GeneralNotification(
                    title: $notificationData['title'],
                    body: $notificationData['body'],
                    data: [
                        'type' => $notificationData['type'],
                        'trip_id' => $trip->id,
                    ]
                ));
            }
        }

        return new TripResource($trip);
    }

    public function getEarnings(Request $request)
    {
        $driver = $request->user();
        $completedTripsQuery = $driver->tripsAsDriver()->where('status', 'completed');
        
        $totalEarnings = $completedTripsQuery->sum('driver_earning');
        $totalTripCount = $completedTripsQuery->count();

        $wallet = $driver->wallet;
        $walletBalance = $wallet ? $wallet->balance : 0;
        $totalPayouts = $wallet ? abs($wallet->transactions()->where('type', 'payout')->sum('amount')) : 0;

        $walletBalance = $driver->wallet->balance;

        $totalPayouts = abs($driver->wallet->transactions()->where('type', 'payout')->sum('amount'));
        
        return response()->json([
            'status' => true,
            'code' => 'earnings_retrieved',
            'message' => 'Earnings retrieved successfully.',
            'data' => [
                'total_earnings' => (float) $totalEarnings,
                'total_trip_count' => (int) $totalTripCount,
                'current_dues' => (float) $walletBalance, // المستحقات الحالية
                'transferred_earnings' => (float) $totalPayouts, // الأرباح المحولة
            ]
        ]);
    }

    public function updateStudentStatus(UpdateStudentStatusRequest $request, SchoolSubscription $subscription)
    {
        $trip = $subscription->trip;
        if (request()->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized_not_trip_driver')], 403);
        }
        
        $data = $request->validated();
        $tripDate = $data['trip_date'];
        
        $dailyStatuses = $subscription->daily_status ?? [];
        $dailyStatuses[$tripDate] = $data['status'];
        
        $subscription->daily_status = $dailyStatuses;
        $subscription->save();

        return response()->json([
            'status' => true,
            'message' => "Student status for {$tripDate} updated successfully.",
            'data' => $subscription
        ]);
    }

    public function checkInPassenger(Request $request, Trip $trip): JsonResponse
    {
        if (request()->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::where('id', $data['booking_id'])
                          ->where('trip_id', $trip->id)
                          ->first();

        if (!$booking) {
            return response()->json(['status' => false, 'message' => __('messages.booking_not_found_for_trip')], 404);
        }

        if ($booking->checked_in_at) {
            return response()->json(['status' => false, 'message' => __('messages.passenger_already_checked_in')], 422);
        }

        $booking->update(['checked_in_at' => now()]);
        
        $booking->load('rider');

        return response()->json([
            'status' => true,
            'message' => __('messages.passenger_checked_in_successfully'),
            'data' => [
                'rider_name' => $booking->rider->name,
                'seat_number' => $booking->seat_number,
                'trip_id' => $trip->id,
            ]
        ]);
    }

    public function checkInPassengerByBooking(Request $request, Booking $booking): JsonResponse
    {
        $trip = $booking->trip;
        if (request()->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        if ($booking->checked_in_at) {
            return response()->json(['status' => false, 'message' => __('messages.passenger_already_checked_in')], 422);
        }

        $booking->update(['checked_in_at' => now()]);
        $booking->load('rider');

        return response()->json([
            'status' => true,
            'message' => __('messages.passenger_checked_in_successfully'),
            'data' => [
                'rider_name' => $booking->rider->name,
                'seat_number' => $booking->seat_number,
                'trip_id' => $trip->id,
            ]
        ]);
    }

    public function verifyPrivateHireTrip(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized_not_trip_driver')], 403);
        }
        if ($trip->type !== 'private_hire') {
            return response()->json(['message' => __('messages.invalid_trip_type')], 422);
        }
        if ($trip->status !== 'approved' && $trip->status !== 'on_way') {
            return response()->json(['message' => __('messages.trip_not_available_for_checkin')], 422);
        }

        $trip->load('rider');

        return response()->json([
            'status' => true,
            'message' => __('messages.trip_verified_successfully'),
            'data' => [
                'trip_id' => $trip->id,
                'rider_name' => $trip->rider->name,
                'rider_mobile' => $trip->rider->mobile,
                'trip_type' => $trip->type,
                'status' => $trip->status,
            ]
        ]);
    }
    
    public function reportProblem(StoreProblemReportRequest $request, Trip $trip): JsonResponse
    {
        if (request()->user()->id !== $trip->driver_id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $trip->problemReports()->create([
            'reporter_id' => $request->user()->id,
            'category' => $request->validated()['category'],
            'description' => $request->validated()['description'],
        ]);

        return response()->json([
            'status' => true,
            'message' => __('messages.problem_reported_successfully')
        ]);
    }

    public function getEarningsHistory(Request $request)
    {
        $driver = $request->user();
        $transactions = $driver->wallet->transactions()
                                ->whereIn('type', ['payment', 'refund', 'payout'])
                                ->latest()
                                ->paginate(20);

        return WalletTransactionResource::collection($transactions);
    }

    public function collectPaymentByWallet(CollectWalletPaymentRequest $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->driver_id) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized_not_trip_driver')], 403);
        }
        if ($trip->payment_status === 'paid') {
            return response()->json(['status' => false, 'message' => __('messages.trip_already_paid')], 422);
        }

        $data = $request->validated();
        $rider = User::where('mobile', $data['rider_mobile'])->firstOrFail();

        if ($trip->rider_id !== $rider->id) {
            return response()->json(['status' => false, 'message' => __('messages.rider_mismatch')], 422);
        }
        $riderWallet = $rider->wallet;
        if (!$riderWallet || $riderWallet->balance < $trip->total_fare) {
            return response()->json(['status' => false, 'message' => __('messages.insufficient_rider_wallet_balance')], 422);
        }

        try {
            DB::transaction(function () use ($trip, $rider, $request) {
                $driver = $request->user();

                $rider->wallet()->decrement('balance', $trip->total_fare);
                $rider->wallet->transactions()->create([
                    'amount' => -$trip->total_fare,
                    'type' => 'payment',
                    'description' => ['en' => "Payment for Trip #{$trip->id}", 'ar' => "دفعة لرحلة رقم #{$trip->id}"],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);

                $driver->wallet()->increment('balance', $trip->driver_earning);
                $driver->wallet->transactions()->create([
                    'amount' => $trip->driver_earning,
                    'type' => 'payment',
                    'description' => ['en' => "Earning from Trip #{$trip->id}", 'ar' => "أرباح من رحلة رقم #{$trip->id}"],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);

                $trip->payment_status = 'paid';
                $trip->save();
            });
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('messages.transaction_error'), 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.payment_collected_successfully'),
            'data' => new \App\Http\Resources\Api\V1\Driver\TripResource($trip->fresh())
        ]);
    }

    // Removed non-production test endpoint sendTestNotification
} 