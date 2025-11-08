<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use App\Models\City;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoutesSeeder extends Seeder
{
    public function run(): void
    {
        // التحقق من وجود المدن والمحطات
        $cities = City::all();
        $stops = Stop::all();

        if ($cities->isEmpty() || $stops->isEmpty()) {
            $this->command->warn('⚠️ يجب تشغيل CitiesSeeder و StopsSeeder أولاً!');
            return;
        }

        $routes = [
            // 1. الرياض → جدة (مسار رئيسي)
            [
                'name' => ['ar' => 'الرياض - جدة السريع', 'en' => 'Riyadh - Jeddah Express'],
                'start_city' => 'الرياض',
                'end_city' => 'جدة',
                'start_point_name' => ['ar' => 'محطة الرياض المركزية', 'en' => 'Riyadh Central Station'],
                'end_point_name' => ['ar' => 'محطة جدة الرئيسية', 'en' => 'Jeddah Main Station'],
                'range_km' => 950,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الملك فهد', 'arrival' => '08:00', 'departure' => '08:15', 'order' => 1],
                    ['name' => 'محطة العليا', 'arrival' => '08:45', 'departure' => '09:00', 'order' => 2],
                    ['name' => 'محطة الدوادمي - طريق الرياض جدة', 'arrival' => '11:00', 'departure' => '11:30', 'order' => 3],
                    ['name' => 'محطة الكورنيش', 'arrival' => '14:00', 'departure' => '14:00', 'order' => 4],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'round_trip',
                        'departure_time' => '08:00',
                        'arrival_time' => '14:00',
                        'return_departure_time' => '16:00',
                        'return_arrival_time' => '22:00',
                        'fare' => 150.00,
                        'return_fare' => 150.00,
                        'round_trip_discount' => 50.00,
                        'days_of_week' => ['Saturday', 'Sunday', 'Monday', 'Wednesday'],
                        'available_seats' => 50,
                    ],
                    [
                        'trip_type' => 'one_way',
                        'departure_time' => '10:00',
                        'arrival_time' => '16:00',
                        'fare' => 150.00,
                        'days_of_week' => ['Tuesday', 'Thursday', 'Friday'],
                        'available_seats' => 45,
                    ],
                ]
            ],

            // 2. الرياض → الدمام
            [
                'name' => ['ar' => 'الرياض - الدمام', 'en' => 'Riyadh - Dammam'],
                'start_city' => 'الرياض',
                'end_city' => 'الدمام',
                'start_point_name' => ['ar' => 'محطة الرياض', 'en' => 'Riyadh Station'],
                'end_point_name' => ['ar' => 'محطة الدمام', 'en' => 'Dammam Station'],
                'range_km' => 395,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الملك فهد', 'arrival' => '09:00', 'departure' => '09:15', 'order' => 1],
                    ['name' => 'محطة الخرج', 'arrival' => '10:00', 'departure' => '10:15', 'order' => 2],
                    ['name' => 'محطة الكورنيش الشمالي', 'arrival' => '13:00', 'departure' => '13:00', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'round_trip',
                        'departure_time' => '09:00',
                        'arrival_time' => '13:00',
                        'return_departure_time' => '15:00',
                        'return_arrival_time' => '19:00',
                        'fare' => 100.00,
                        'return_fare' => 100.00,
                        'round_trip_discount' => 30.00,
                        'days_of_week' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                        'available_seats' => 48,
                    ],
                ]
            ],

            // 3. جدة → مكة
            [
                'name' => ['ar' => 'جدة - مكة المكرمة', 'en' => 'Jeddah - Makkah'],
                'start_city' => 'جدة',
                'end_city' => 'مكة المكرمة',
                'start_point_name' => ['ar' => 'محطة جدة', 'en' => 'Jeddah Station'],
                'end_point_name' => ['ar' => 'محطة مكة', 'en' => 'Makkah Station'],
                'range_km' => 79,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الكورنيش', 'arrival' => '07:00', 'departure' => '07:15', 'order' => 1],
                    ['name' => 'محطة البلد', 'arrival' => '07:30', 'departure' => '07:45', 'order' => 2],
                    ['name' => 'محطة العزيزية', 'arrival' => '08:30', 'departure' => '08:30', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'round_trip',
                        'departure_time' => '07:00',
                        'arrival_time' => '08:30',
                        'return_departure_time' => '18:00',
                        'return_arrival_time' => '19:30',
                        'fare' => 40.00,
                        'return_fare' => 40.00,
                        'round_trip_discount' => 15.00,
                        'days_of_week' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                        'available_seats' => 50,
                    ],
                ]
            ],

            // 4. مكة → المدينة
            [
                'name' => ['ar' => 'مكة - المدينة المنورة', 'en' => 'Makkah - Madinah'],
                'start_city' => 'مكة المكرمة',
                'end_city' => 'المدينة المنورة',
                'start_point_name' => ['ar' => 'محطة مكة', 'en' => 'Makkah Station'],
                'end_point_name' => ['ar' => 'محطة المدينة', 'en' => 'Madinah Station'],
                'range_km' => 450,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة العزيزية', 'arrival' => '10:00', 'departure' => '10:15', 'order' => 1],
                    ['name' => 'محطة المسفلة', 'arrival' => '10:30', 'departure' => '10:45', 'order' => 2],
                    ['name' => 'محطة قباء', 'arrival' => '15:30', 'departure' => '15:30', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'one_way',
                        'departure_time' => '10:00',
                        'arrival_time' => '15:30',
                        'fare' => 120.00,
                        'days_of_week' => ['Friday', 'Saturday', 'Sunday'],
                        'available_seats' => 45,
                    ],
                ]
            ],

            // 5. الدمام → الخبر
            [
                'name' => ['ar' => 'الدمام - الخبر', 'en' => 'Dammam - Khobar'],
                'start_city' => 'الدمام',
                'end_city' => 'الخبر',
                'start_point_name' => ['ar' => 'محطة الدمام', 'en' => 'Dammam Station'],
                'end_point_name' => ['ar' => 'محطة الخبر', 'en' => 'Khobar Station'],
                'range_km' => 25,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الكورنيش الشمالي', 'arrival' => '08:00', 'departure' => '08:10', 'order' => 1],
                    ['name' => 'محطة الفيصلية', 'arrival' => '08:20', 'departure' => '08:30', 'order' => 2],
                    ['name' => 'محطة الراكة', 'arrival' => '08:45', 'departure' => '08:45', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'round_trip',
                        'departure_time' => '08:00',
                        'arrival_time' => '08:45',
                        'return_departure_time' => '17:00',
                        'return_arrival_time' => '17:45',
                        'fare' => 25.00,
                        'return_fare' => 25.00,
                        'round_trip_discount' => 10.00,
                        'days_of_week' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                        'available_seats' => 40,
                    ],
                ]
            ],

            // 6. الرياض → القصيم
            [
                'name' => ['ar' => 'الرياض - بريدة', 'en' => 'Riyadh - Buraydah'],
                'start_city' => 'الرياض',
                'end_city' => 'القصيم',
                'start_point_name' => ['ar' => 'محطة الرياض', 'en' => 'Riyadh Station'],
                'end_point_name' => ['ar' => 'محطة بريدة', 'en' => 'Buraydah Station'],
                'range_km' => 330,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الملك فهد', 'arrival' => '09:00', 'departure' => '09:15', 'order' => 1],
                    ['name' => 'محطة الرس - طريق القصيم', 'arrival' => '11:00', 'departure' => '11:20', 'order' => 2],
                    ['name' => 'محطة بريدة المركزية', 'arrival' => '13:00', 'departure' => '13:00', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'one_way',
                        'departure_time' => '09:00',
                        'arrival_time' => '13:00',
                        'fare' => 80.00,
                        'days_of_week' => ['Saturday', 'Monday', 'Wednesday', 'Thursday'],
                        'available_seats' => 50,
                    ],
                ]
            ],

            // 7. جدة → الطائف
            [
                'name' => ['ar' => 'جدة - الطائف', 'en' => 'Jeddah - Taif'],
                'start_city' => 'جدة',
                'end_city' => 'الطائف',
                'start_point_name' => ['ar' => 'محطة جدة', 'en' => 'Jeddah Station'],
                'end_point_name' => ['ar' => 'محطة الطائف', 'en' => 'Taif Station'],
                'range_km' => 167,
                'is_active' => true,
                'stops' => [
                    ['name' => 'محطة الكورنيش', 'arrival' => '14:00', 'departure' => '14:15', 'order' => 1],
                    ['name' => 'محطة حي الحمراء', 'arrival' => '14:40', 'departure' => '14:50', 'order' => 2],
                    ['name' => 'محطة الحوية', 'arrival' => '16:30', 'departure' => '16:30', 'order' => 3],
                ],
                'schedules' => [
                    [
                        'trip_type' => 'round_trip',
                        'departure_time' => '14:00',
                        'arrival_time' => '16:30',
                        'return_departure_time' => '18:00',
                        'return_arrival_time' => '20:30',
                        'fare' => 60.00,
                        'return_fare' => 60.00,
                        'round_trip_discount' => 20.00,
                        'days_of_week' => ['Friday', 'Saturday', 'Sunday'],
                        'available_seats' => 45,
                    ],
                ]
            ],

            // 8. مسار غير نشط (للاختبار)
            [
                'name' => ['ar' => 'مسار تحت الصيانة', 'en' => 'Under Maintenance Route'],
                'start_city' => 'الرياض',
                'end_city' => 'أبها',
                'start_point_name' => ['ar' => 'الرياض', 'en' => 'Riyadh'],
                'end_point_name' => ['ar' => 'أبها', 'en' => 'Abha'],
                'range_km' => 850,
                'is_active' => false,
                'stops' => [],
                'schedules' => []
            ],
        ];

        $createdRoutes = 0;
        $createdStops = 0;
        $createdSchedules = 0;

        foreach ($routes as $routeData) {
            try {
                // البحث عن المدن
                $startCity = City::where('name->ar', $routeData['start_city'])->first();
                $endCity = City::where('name->ar', $routeData['end_city'])->first();

                if (!$startCity || !$endCity) {
                    $this->command->warn("⚠️ لم يتم العثور على المدن: {$routeData['start_city']} → {$routeData['end_city']}");
                    continue;
                }

                // إنشاء المسار
                $route = Route::create([
                    'name' => $routeData['name'],
                    'start_city_id' => $startCity->id,
                    'end_city_id' => $endCity->id,
                    'start_point_name' => $routeData['start_point_name'],
                    'end_point_name' => $routeData['end_point_name'],
                    'range_km' => $routeData['range_km'],
                    'is_active' => $routeData['is_active'],
                ]);

                $createdRoutes++;

                // إضافة المحطات
                foreach ($routeData['stops'] as $stopData) {
                    $stop = Stop::where('name->ar', 'like', '%' . $stopData['name'] . '%')->first();

                    if ($stop) {
                        RouteStop::create([
                            'route_id' => $route->id,
                            'stop_id' => $stop->id,
                            'arrival_time' => $stopData['arrival'],
                            'departure_time' => $stopData['departure'],
                            'order' => $stopData['order'],
                        ]);
                        $createdStops++;
                    }
                }

                // إضافة الجداول
                foreach ($routeData['schedules'] as $scheduleData) {
                    // البحث عن سائق متاح
                    $driver = User::where('user_type', 'driver')
                        ->whereHas('driver', function ($q) {
                            $q->where('availability_status', 'available');
                        })
                        ->inRandomOrder()
                        ->first();

                    $schedule = Schedule::create([
                        'route_id' => $route->id,
                        'driver_id' => $driver?->id,
                        'trip_type' => $scheduleData['trip_type'],
                        'departure_time' => $scheduleData['departure_time'],
                        'arrival_time' => $scheduleData['arrival_time'],
                        'return_departure_time' => $scheduleData['return_departure_time'] ?? null,
                        'return_arrival_time' => $scheduleData['return_arrival_time'] ?? null,
                        'fare' => $scheduleData['fare'],
                        'return_fare' => $scheduleData['return_fare'] ?? null,
                        'round_trip_discount' => $scheduleData['round_trip_discount'] ?? null,
                        'days_of_week' => $scheduleData['days_of_week'],
                        'available_seats' => $scheduleData['available_seats'],
                        'is_active' => true,
                    ]);

                    // إضافة محطات الجدول (الذهاب)
                    $routeStops = RouteStop::where('route_id', $route->id)->orderBy('order')->get();
                    foreach ($routeStops as $routeStop) {
                        ScheduleStop::create([
                            'schedule_id' => $schedule->id,
                            'stop_id' => $routeStop->stop_id,
                            'direction' => 'outbound',
                            'arrival_time' => $routeStop->arrival_time,
                            'departure_time' => $routeStop->departure_time,
                            'order' => $routeStop->order,
                        ]);
                    }

                    // إضافة محطات العودة (إذا كانت round_trip)
                    if ($scheduleData['trip_type'] === 'round_trip') {
                        $reversedStops = $routeStops->reverse()->values();
                        foreach ($reversedStops as $index => $routeStop) {
                            ScheduleStop::create([
                                'schedule_id' => $schedule->id,
                                'stop_id' => $routeStop->stop_id,
                                'direction' => 'return',
                                'arrival_time' => $routeStop->arrival_time,
                                'departure_time' => $routeStop->departure_time,
                                'order' => $index + 1,
                            ]);
                        }
                    }

                    $createdSchedules++;
                }

                $this->command->info("✅ تم إنشاء المسار: {$route->getTranslation('name', 'ar')}");

            } catch (\Exception $e) {
                $this->command->error("❌ فشل إنشاء المسار: {$routeData['name']['ar']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdRoutes} مسار بنجاح!");
        $this->command->info("✅ تم إضافة {$createdStops} محطة للمسارات");
        $this->command->info("✅ تم إنشاء {$createdSchedules} جدول رحلة");
    }
}
