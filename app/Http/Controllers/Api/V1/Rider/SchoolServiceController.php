<?php
namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Rider\StoreSchoolSubscriptionRequest;
use App\Models\SchoolPackage;
use App\Models\SchoolSubscription;
use App\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\Rider\ReportChildAbsenceRequest;
use App\Models\School;
use App\Models\Notification;
use App\Notifications\GeneralNotification;

class SchoolServiceController extends Controller
{
    public function index() {
        return SchoolPackage::where('is_active', true)->get()->map(fn($p) => [
            'id' => $p->id,
            'name' => $p->getTranslation('name', app()->getLocale()),
            'description' => $p->getTranslation('description', app()->getLocale()),
            'price' => $p->price,
            'duration_days' => $p->duration_days,
        ]);
    }

    public function subscribe(StoreSchoolSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rider = $request->user();
        $package = SchoolPackage::findOrFail($data['package_id']);
        $school = School::findOrFail($data['school_id']);
        $childrenCount = count($data['children_ids']);
        $children = $rider->children()->whereIn('id', $data['children_ids'])->get();

        if ($children->count() !== $childrenCount) {
            return response()->json(['message' => __('messages.children_not_belong_to_you')], 403);
        }
        
        if (!$school->packages()->where('school_package_id', $package->id)->exists()) {
            return response()->json(['status' => false, 'message' => __('messages.package_not_available_for_school')], 422);
        }

        return DB::transaction(function () use ($data, $rider, $package, $children, $childrenCount, $school, $request) {
            $totalFare = $package->price * $childrenCount;
            if ($data['payment_method'] === 'wallet') {
                if ($rider->wallet->balance < $totalFare) {
                    return response()->json(['message' => 'Insufficient wallet balance.'], 422);
                }
                $rider->wallet->decrement('balance', $totalFare);
            }
            $trip = Trip::create([
                'rider_id' => $rider->id,
                'type' => 'school_service',
                'status' => 'approved',
                'trip_datetime' => $data['start_date'],
                'pickup_lat' => $data['pickup_lat'],
                'pickup_lng' => $data['pickup_lng'],
                'pickup_address' => $data['pickup_address'],
                'dropoff_lat' => $school->lat,
                'dropoff_lng' => $school->lng,
                'dropoff_address' => $school->getTranslation('name', 'ar'),
                'total_fare' => $totalFare,
                'payment_method' => $data['payment_method'],
                'payment_status' => ($data['payment_method'] === 'wallet') ? 'paid' : 'pending',
                'tripable_id' => $school->id,
                'tripable_type' => School::class,
            ]);
            
            if ($data['payment_method'] === 'wallet') {
                $rider->wallet->transactions()->create([
                    'amount' => -$totalFare,
                    'type' => 'payment',
                    'description' => [
                        'en' => "Payment for School Subscription #{$trip->id}",
                        'ar' => "دفعة اشتراك مدرسي رقم #{$trip->id}"
                    ],
                    'related_id' => $trip->id,
                    'related_type' => Trip::class,
                ]);
            }

            foreach ($children as $child) {
                SchoolSubscription::create([
                    'trip_id' => $trip->id,
                    'child_id' => $child->id,
                    'school_package_id' => $package->id,
                    'start_date' => $data['start_date'],
                    'end_date' => now()->parse($data['start_date'])->addDays($package->duration_days),
                    'status' => 'active',
                ]);
            }

            // Set the locale based on Accept-Language header or use Arabic as default
            $locale = $request->header('Accept-Language', 'ar');
            if (strpos($locale, 'ar') !== false) {
                $locale = 'ar';
            } else {
                $locale = 'en';
            }
            
            // Set the locale temporarily
            app()->setLocale($locale);
            
            $successMessage = __('messages.subscription_confirmed_with_id', ['id' => $trip->id]);
            // Replace the placeholder manually if Laravel doesn't work
            $successMessage = str_replace('{id}', $trip->id, $successMessage);
            
            return response()->json([
                'status' => true,
                'message' => __('messages.subscribed_successfully'),
                'data' => [
                    'subscription_id' => $trip->id,
                    'subscription_trip_id' => $trip->id,
                    'total_fare' => $totalFare,
                    'success_message' => $successMessage,
                ]
            ], 201);
        });
    }

    public function mySchoolSubscriptions(Request $request)
    {
        $user = $request->user();
        $childrenIds = $user->children()->pluck('id');
        $subscriptions = \App\Models\SchoolSubscription::with(['schoolPackage', 'trip', 'child'])
            ->whereIn('child_id', $childrenIds)
            ->latest('start_date')
            ->get();
        return response()->json([
            'status' => true,
            'code' => 'subscriptions_found',
            'data' => $subscriptions
        ]);
    }

    public function reportChildAbsence(ReportChildAbsenceRequest $request, SchoolSubscription $subscription): JsonResponse
    {
        if ($subscription->child->parent_id !== $request->user()->id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $data = $request->validated();
        $absenceDate = $data['absence_date'];

        $dailyStatuses = $subscription->daily_status ?? [];
        $dailyStatuses[$absenceDate] = 'absent_by_parent';
        
        $subscription->daily_status = $dailyStatuses;
        $subscription->save();

        // Send notification to driver about student absence
        $trip = $subscription->trip;
        if ($trip && $trip->driver) {
            $absenceDate = $request->validated()['absence_date'];
            $trip->driver->notify(new GeneralNotification(
                title: ['en' => 'Student Absence', 'ar' => 'غياب طالب'],
                body: ['en' => "Student {$subscription->child->name} has been marked as absent for {$absenceDate}.", 'ar' => "تم تسجيل الطالب {$subscription->child->name} كـ غائب لتاريخ {$absenceDate}."],
                data: [
                    'type' => 'student_absent',
                    'trip_id' => $trip->id,
                    'child_id' => $subscription->child_id,
                ]
            ));
        }

        return response()->json([
            'status' => true,
            'message' => "Child absence for {$absenceDate} has been reported successfully.",
            'data' => $subscription
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'home_lat' => ['required', 'numeric', 'between:-90,90'],
            'home_lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['sometimes', 'numeric', 'min:1', 'max:100']
        ]);

        $radius = $data['radius_km'] ?? 20;

        $schools = School::selectRaw("id, name, lat, lng, departure_time, return_time, ( 6371 * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) AS distance", [$data['home_lat'], $data['home_lng'], $data['home_lat']])
            ->where('is_active', true)
            ->having("distance", "<=", $radius)
            ->with('packages')
            ->orderBy("distance", 'asc')
            ->get();
        
        $results = $schools->filter(function ($school) {
            return $school->packages->isNotEmpty();
        });

        return response()->json([
            'status' => true,
            'data' => $results->values()->map(function ($school) {
                return [
                    'school' => [
                        'id' => $school->id,
                        'name' => $school->getTranslation('name', app()->getLocale()),
                        'lat' => $school->lat,
                        'lng' => $school->lng,
                        'departure_time' => $school->departure_time ? $school->departure_time->format('H:i') : null,
                        'return_time' => $school->return_time ? $school->return_time->format('H:i') : null,
                        'distance' => round($school->distance, 2)
                    ],
                    'packages' => $school->packages->map(function ($package) {
                        return [
                            'id' => $package->id,
                            'name' => $package->getTranslation('name', app()->getLocale()),
                            'description' => $package->getTranslation('description', app()->getLocale()),
                            'price' => $package->price,
                            'duration_days' => $package->duration_days,
                        ];
                    })
                ];
            })
        ]);
    }
}