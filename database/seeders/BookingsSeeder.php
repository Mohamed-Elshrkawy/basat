<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use App\Models\City;
use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingsSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على المستخدمين
        $passengers = User::where('user_type', 'customer')
            ->where('is_active', true)
            ->get();

        if ($passengers->isEmpty()) {
            $this->command->warn('⚠️ لا يوجد مستخدمين (ركاب) في قاعدة البيانات!');
            return;
        }

        // الحصول على الجداول النشطة
        $schedules = Schedule::where('is_active', true)
            ->where('available_seats', '>', 0)
            ->get();

        if ($schedules->isEmpty()) {
            $this->command->warn('⚠️ لا توجد جداول نشطة في قاعدة البيانات!');
            return;
        }

        // الحصول على سائقي الحافلات الخاصة
        $privateDrivers = User::where('user_type', 'driver')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereHas('vehicle', function ($q) {
                $q->where('type', 'private_bus')->where('is_active', true);
            })
            ->with(['vehicle.amenities'])
            ->get();

        $createdCount = 0;
        $publicBookingsCount = 0;
        $privateBookingsCount = 0;

        // ========== حجوزات الحافلات العامة (Public Bus) ==========

        // حجز 1: رحلة ذهاب فقط - مؤكدة ومدفوعة
        $schedule = $schedules->first();
        if ($schedule && $schedule->scheduleStops()->where('direction', 'outbound')->count() >= 2) {
            $stops = $schedule->scheduleStops()->where('direction', 'outbound')->orderBy('order')->get();
            $boardingStop = $stops->first();
            $droppingStop = $stops->last();

            $booking = Booking::create([
                'type' => 'public_bus',
                'user_id' => $passengers->random()->id,
                'schedule_id' => $schedule->id,
                'trip_type' => 'one_way',
                'travel_date' => Carbon::today()->addDays(2),
                'number_of_seats' => 2,
                'seat_numbers' => [15, 16],
                'outbound_boarding_stop_id' => $boardingStop->id,
                'outbound_dropping_stop_id' => $droppingStop->id,
                'outbound_fare' => $schedule->fare,
                'discount' => 0,
                'total_amount' => $schedule->fare * 2,
                'payment_method' => 'wallet',
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'passenger_status' => 'pending',
                'paid_at' => now(),
            ]);

            $schedule->decrement('available_seats', 2);
            $publicBookingsCount++;
            $createdCount++;
            $this->command->info("✅ حجز عام #{$booking->booking_number}");
        }

        // حجز 2: رحلة ذهاب وعودة - مؤكدة ومدفوعة
        $roundTripSchedule = $schedules->where('trip_type', 'round_trip')->first();
        if ($roundTripSchedule &&
            $roundTripSchedule->scheduleStops()->where('direction', 'outbound')->count() >= 2 &&
            $roundTripSchedule->scheduleStops()->where('direction', 'return')->count() >= 2) {

            $outboundStops = $roundTripSchedule->scheduleStops()->where('direction', 'outbound')->orderBy('order')->get();
            $returnStops = $roundTripSchedule->scheduleStops()->where('direction', 'return')->orderBy('order')->get();

            $booking = Booking::create([
                'type' => 'public_bus',
                'user_id' => $passengers->random()->id,
                'schedule_id' => $roundTripSchedule->id,
                'trip_type' => 'round_trip',
                'travel_date' => Carbon::today()->addDays(5),
                'return_date' => Carbon::today()->addDays(7),
                'number_of_seats' => 1,
                'seat_numbers' => [20],
                'outbound_boarding_stop_id' => $outboundStops->first()->id,
                'outbound_dropping_stop_id' => $outboundStops->last()->id,
                'return_boarding_stop_id' => $returnStops->first()->id,
                'return_dropping_stop_id' => $returnStops->last()->id,
                'outbound_fare' => $roundTripSchedule->fare,
                'return_fare' => $roundTripSchedule->return_fare,
                'discount' => $roundTripSchedule->round_trip_discount ?? 0,
                'total_amount' => ($roundTripSchedule->fare + $roundTripSchedule->return_fare) - ($roundTripSchedule->round_trip_discount ?? 0),
                'payment_method' => 'card',
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'passenger_status' => 'pending',
                'paid_at' => now(),
                'transaction_id' => 'CARD_' . now()->timestamp,
            ]);

            $roundTripSchedule->decrement('available_seats', 1);
            $publicBookingsCount++;
            $createdCount++;
            $this->command->info("✅ حجز عام ذهاب وعودة #{$booking->booking_number}");
        }

        // حجز 3: حجز معلق - لم يتم الدفع
        if ($schedules->count() > 1) {
            $schedule2 = $schedules->skip(1)->first();
            if ($schedule2 && $schedule2->scheduleStops()->where('direction', 'outbound')->count() >= 2) {
                $stops = $schedule2->scheduleStops()->where('direction', 'outbound')->orderBy('order')->get();

                $booking = Booking::create([
                    'type' => 'public_bus',
                    'user_id' => $passengers->random()->id,
                    'schedule_id' => $schedule2->id,
                    'trip_type' => 'one_way',
                    'travel_date' => Carbon::today()->addDays(3),
                    'number_of_seats' => 1,
                    'seat_numbers' => [10],
                    'outbound_boarding_stop_id' => $stops->first()->id,
                    'outbound_dropping_stop_id' => $stops->last()->id,
                    'outbound_fare' => $schedule2->fare,
                    'discount' => 0,
                    'total_amount' => $schedule2->fare,
                    'payment_method' => 'cash',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'passenger_status' => 'pending',
                ]);

                $publicBookingsCount++;
                $createdCount++;
                $this->command->info("✅ حجز عام معلق #{$booking->booking_number}");
            }
        }

        // حجز 4: حجز ملغي
        if ($schedules->count() > 2) {
            $schedule3 = $schedules->skip(2)->first();
            if ($schedule3 && $schedule3->scheduleStops()->where('direction', 'outbound')->count() >= 2) {
                $stops = $schedule3->scheduleStops()->where('direction', 'outbound')->orderBy('order')->get();

                $booking = Booking::create([
                    'type' => 'public_bus',
                    'user_id' => $passengers->random()->id,
                    'schedule_id' => $schedule3->id,
                    'trip_type' => 'one_way',
                    'travel_date' => Carbon::today()->addDays(1),
                    'number_of_seats' => 3,
                    'seat_numbers' => [5, 6, 7],
                    'outbound_boarding_stop_id' => $stops->first()->id,
                    'outbound_dropping_stop_id' => $stops->last()->id,
                    'outbound_fare' => $schedule3->fare,
                    'discount' => 0,
                    'total_amount' => $schedule3->fare * 3,
                    'payment_method' => 'wallet',
                    'payment_status' => 'paid',
                    'status' => 'cancelled',
                    'passenger_status' => 'pending',
                    'paid_at' => now()->subDays(1),
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'طلب الراكب إلغاء الحجز',
                ]);

                $publicBookingsCount++;
                $createdCount++;
                $this->command->info("✅ حجز عام ملغي #{$booking->booking_number}");
            }
        }

        // ========== حجوزات الحافلات الخاصة (Private Bus) ==========

        if ($privateDrivers->isNotEmpty()) {
            $cities = City::all();

            // حجز خاص 1: رحلة قصيرة - مؤكدة ومدفوعة
            if ($cities->count() >= 2) {
                $driver = $privateDrivers->first();
                $startCity = $cities->random();
                $endCity = $cities->where('id', '!=', $startCity->id)->random();

                // حساب المسافة
                $distance = $this->calculateDistance(
                    $startCity->lat, $startCity->lng,
                    $endCity->lat, $endCity->lng
                );

                $baseFare = max(100, $distance * 2.5);
                $amenitiesCost = 0;
                $amenitiesData = [];

                // إضافة وسائل راحة
                if ($driver->vehicle->amenities->count() > 0) {
                    $selectedAmenities = $driver->vehicle->amenities->random(min(2, $driver->vehicle->amenities->count()));
                    foreach ($selectedAmenities as $amenity) {
                        $price = $amenity->pivot->price ?? 0;
                        $amenitiesCost += $price;
                        $amenitiesData[$amenity->id] = ['price' => $price];
                    }
                }

                $booking = Booking::create([
                    'type' => 'private_bus',
                    'user_id' => $passengers->random()->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $driver->vehicle->id,
                    'start_city_id' => $startCity->id,
                    'end_city_id' => $endCity->id,
                    'trip_type' => 'one_way',
                    'travel_date' => Carbon::today()->addDays(3),
                    'number_of_seats' => 15,
                    'seat_numbers' => [],
                    'distance_km' => $distance,
                    'base_fare' => $baseFare,
                    'amenities_cost' => $amenitiesCost,
                    'total_days' => 1,
                    'discount' => 0,
                    'total_amount' => $baseFare + $amenitiesCost,
                    'payment_method' => 'wallet',
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'trip_status' => 'pending',
                    'paid_at' => now(),
                    'transaction_id' => 'WALLET_' . now()->timestamp,
                ]);

                if (!empty($amenitiesData)) {
                    $booking->amenities()->attach($amenitiesData);
                }

                $privateBookingsCount++;
                $createdCount++;
                $this->command->info("✅ حجز خاص #{$booking->booking_number}");
            }

            // حجز خاص 2: رحلة ذهاب وعودة - مؤكدة
            if ($privateDrivers->count() > 1 && $cities->count() >= 2) {
                $driver = $privateDrivers->skip(1)->first();
                $startCity = $cities->random();
                $endCity = $cities->where('id', '!=', $startCity->id)->random();

                $distance = $this->calculateDistance(
                    $startCity->lat, $startCity->lng,
                    $endCity->lat, $endCity->lng
                );

                $totalDays = 3;
                $baseFare = max(100, $distance * 2.5 * $totalDays);

                $booking = Booking::create([
                    'type' => 'private_bus',
                    'user_id' => $passengers->random()->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $driver->vehicle->id,
                    'start_city_id' => $startCity->id,
                    'end_city_id' => $endCity->id,
                    'trip_type' => 'round_trip',
                    'travel_date' => Carbon::today()->addDays(7),
                    'return_date' => Carbon::today()->addDays(9),
                    'number_of_seats' => 25,
                    'seat_numbers' => [],
                    'distance_km' => $distance,
                    'base_fare' => $baseFare,
                    'amenities_cost' => 0,
                    'total_days' => $totalDays,
                    'discount' => 0,
                    'total_amount' => $baseFare,
                    'payment_method' => 'card',
                    'payment_status' => 'paid',
                    'status' => 'confirmed',
                    'trip_status' => 'pending',
                    'paid_at' => now(),
                    'transaction_id' => 'CARD_' . (now()->timestamp + 1),
                ]);

                $privateBookingsCount++;
                $createdCount++;
                $this->command->info("✅ حجز خاص ذهاب وعودة #{$booking->booking_number}");
            }

            // حجز خاص 3: رحلة معلقة - لم يتم الدفع
            if ($privateDrivers->count() > 2 && $cities->count() >= 2) {
                $driver = $privateDrivers->skip(2)->first();
                $startCity = $cities->random();
                $endCity = $cities->where('id', '!=', $startCity->id)->random();

                $distance = $this->calculateDistance(
                    $startCity->lat, $startCity->lng,
                    $endCity->lat, $endCity->lng
                );

                $baseFare = max(100, $distance * 2.5);

                $booking = Booking::create([
                    'type' => 'private_bus',
                    'user_id' => $passengers->random()->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $driver->vehicle->id,
                    'start_city_id' => $startCity->id,
                    'end_city_id' => $endCity->id,
                    'trip_type' => 'one_way',
                    'travel_date' => Carbon::today()->addDays(10),
                    'number_of_seats' => 10,
                    'seat_numbers' => [],
                    'distance_km' => $distance,
                    'base_fare' => $baseFare,
                    'amenities_cost' => 0,
                    'total_days' => 1,
                    'discount' => 0,
                    'total_amount' => $baseFare,
                    'payment_method' => 'cash',
                    'payment_status' => 'pending',
                    'status' => 'pending',
                    'trip_status' => 'pending',
                ]);

                $privateBookingsCount++;
                $createdCount++;
                $this->command->info("✅ حجز خاص معلق #{$booking->booking_number}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} حجز بنجاح!");
        $this->command->info("   - حجوزات عامة: {$publicBookingsCount}");
        $this->command->info("   - حجوزات خاصة: {$privateBookingsCount}");
    }

    /**
     * حساب المسافة بين نقطتين باستخدام Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // نصف قطر الأرض بالكيلومتر

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
