<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SchoolSubscription;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CreateDailySchoolTrips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-daily-school-trips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates daily school service trips for active subscriptions.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $this->info("Starting to create daily school trips for: {$today->toDateString()}");

        $activeSubscriptions = SchoolSubscription::where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->with('trip') // Eager load the main trip contract
            ->get();

        if ($activeSubscriptions->isEmpty()) {
            $this->info("No active subscriptions for today. Exiting.");
            return;
        }

        $this->info("Found {$activeSubscriptions->count()} active subscriptions.");

        foreach ($activeSubscriptions as $subscription) {
            if (!$today->isWeekday()) { // isWeekday -> Mon to Fri
                $this->comment("Skipping subscription #{$subscription->id}: Today is a weekend.");
                continue;
            }

            $tripExists = $subscription->dailyTrips()
                                       ->whereDate('trip_datetime', $today)
                                       ->exists();

            if ($tripExists) {
                $this->comment("Skipping subscription #{$subscription->id}: Daily trip already exists.");
                continue;
            }

            $parentTrip = $subscription->trip;
            if (!$parentTrip) {
                Log::warning("Skipping subscription #{$subscription->id}: Parent trip contract not found.");
                continue;
            }

            Trip::create([
                'rider_id' => $parentTrip->rider_id,
                'driver_id' => $parentTrip->driver_id,
                'vehicle_id' => $parentTrip->vehicle_id,
                'type' => 'school_service',
                'status' => 'approved',
                'trip_datetime' => $today->copy()->setTime(6, 30, 0), // 6:30 AM
                'pickup_address' => $parentTrip->pickup_address,
                'pickup_lat' => $parentTrip->pickup_lat,
                'pickup_lng' => $parentTrip->pickup_lng,
                'dropoff_address' => $parentTrip->dropoff_address,
                'dropoff_lat' => $parentTrip->dropoff_lat,
                'dropoff_lng' => $parentTrip->dropoff_lng,
                'total_fare' => 0,
                'payment_method' => $parentTrip->payment_method,
                'payment_status' => 'paid',
                'tripable_id' => $subscription->id,
                'tripable_type' => SchoolSubscription::class,
                'booking_type' => 'children',
            ]);

            Trip::create([
                'rider_id' => $parentTrip->rider_id,
                'driver_id' => $parentTrip->driver_id,
                'vehicle_id' => $parentTrip->vehicle_id,
                'type' => 'school_service',
                'status' => 'approved',
                'trip_datetime' => $today->copy()->setTime(14, 0, 0), // 2:00 PM
                'pickup_address' => $parentTrip->dropoff_address,
                'pickup_lat' => $parentTrip->dropoff_lat,
                'pickup_lng' => $parentTrip->dropoff_lng,
                'dropoff_address' => $parentTrip->pickup_address,
                'dropoff_lat' => $parentTrip->pickup_lat,
                'dropoff_lng' => $parentTrip->pickup_lng,
                'total_fare' => 0,
                'payment_method' => $parentTrip->payment_method,
                'payment_status' => 'paid',
                'tripable_id' => $subscription->id,
                'tripable_type' => SchoolSubscription::class,
                'booking_type' => 'children',
            ]);

            $this->info("Created morning and afternoon trips for subscription #{$subscription->id}.");
        }

        $this->info("Daily school trips creation process finished.");
    }
}