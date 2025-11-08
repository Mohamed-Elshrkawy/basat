<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\Rider\StoreRatingRequest;
use App\Http\Requests\Api\V1\StoreProblemReportRequest;
use App\Models\Notification;
use App\Notifications\GeneralNotification;

class TripController extends Controller
{
    public function cancel(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        if ($trip->status !== 'pending' && $trip->status !== 'approved') {
            return response()->json(['message' => __('messages.cannot_cancel_trip_current_stage')], 422);
        }
        if (now()->addHour() > $trip->trip_datetime) {
            return response()->json(['message' => __('messages.cancellation_window_passed')], 422);
        }
        $trip->status = 'cancelled_by_rider';
        $trip->save();

        // Send notification to driver
        if ($trip->driver) {
            $trip->driver->notify(new GeneralNotification(
                title: ['en' => 'Trip Cancelled', 'ar' => 'تم إلغاء الرحلة'],
                body: ['en' => "Trip #{$trip->id} has been cancelled by the rider.", 'ar' => "الرحلة رقم #{$trip->id} تم إلغاؤها بواسطة الراكب."],
                data: [
                    'type' => 'trip_cancelled_by_rider',
                    'trip_id' => $trip->id,
                ]
            ));
        }
        if ($trip->payment_method === 'wallet' && $trip->payment_status === 'paid') {
            $request->user()->wallet()->increment('balance', $trip->total_fare);
        }
        return response()->json([
            'status' => true,
            'message' => __('messages.trip_cancelled_successfully')
        ]);
    }

    public function rate(StoreRatingRequest $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        // Ensure trip is completed or in the past before rating
        $isPastTrip = $trip->trip_datetime < now();
        if ($trip->status !== 'completed' && !$isPastTrip) {
            return response()->json(['message' => __('messages.can_only_rate_completed_trips')], 422);
        }
        
        $existingRating = $trip->ratings()->where('rater_id', $request->user()->id)->exists();
        if ($existingRating) {
            return response()->json(['message' => __('messages.already_rated_trip')], 422);
        }
        
        $trip->ratings()->create([
            'rater_id' => $request->user()->id,
            'rated_id' => $trip->driver_id,
            'rating' => $request->validated()['rating'],
            'comment' => $request->validated()['comment'] ?? null,
        ]);
        
        // Optionally mark trip as completed once rated
        if ($trip->status !== 'completed') {
            $trip->status = 'completed';
            $trip->save();
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.feedback_thanks')
        ]);
    }

    public function reportProblem(StoreProblemReportRequest $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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

    public function trackDriver(Request $request, Trip $trip): JsonResponse
    {
        if ($request->user()->id !== $trip->rider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$trip->driver || !$trip->driver->driverProfile) {
             return response()->json([
                'status' => false,
                'message' => 'Driver tracking is not available for this trip at the moment.'
            ], 404);
        }

        $driverProfile = $trip->driver->driverProfile;
        
        $lat = $driverProfile->current_lat ?? 24.7136;
        $lng = $driverProfile->current_lng ?? 46.6753;

        return response()->json([
            'status' => true,
            'data' => [
                'lat' => (float)$lat,
                'lng' => (float)$lng,
                'updated_at' => $driverProfile->updated_at->toDateTimeString(),
            ]
        ]);
    }
}