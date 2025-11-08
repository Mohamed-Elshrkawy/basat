<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\PublicBusRoute;
use App\Models\PublicBusSchedule;
use App\Models\Stop;
use App\Models\SchoolPackage;
use App\Models\School;
use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $driverRole = Role::findByName('Driver', 'web');
        $driverUser = User::create([
            'name' => 'Test Driver',
            'national_id' => '1234567890',
            'gender' => 'male',
            'mobile' => '966500000001',
            'password' => Hash::make('password'),
            'mobile_verified_at' => now(),
        ]);
        $driverUser->assignRole($driverRole);
        $driverUser->driverProfile()->create([
            'availability_status' => 'available',
            'bio' => 'Professional driver with extensive experience',
        ]);
        $driverUser->wallet()->create(['balance' => 0]);

        $bus = Vehicle::create([
            'driver_id' => $driverUser->id,
            'brand' => 'Mercedes',
            'model' => 'Tourismo',
            'plate_number' => '123 ABC',
            'seat_count' => 20,
            'type' => 'bus',
            'is_active' => true,
        ]);

        // Keep a single driver and vehicle only for this scenario

        $route = PublicBusRoute::create([
            'name' => ['en' => 'King Fahd Road', 'ar' => 'طريق الملك فهد'],
            'start_point_name' => ['en' => 'North', 'ar' => 'الشمال'],
            'end_point_name' => ['en' => 'South', 'ar' => 'الجنوب'],
            'range_km' => 5,
            'is_active' => true,
        ]);

        Stop::create([
            'route_id' => $route->id,
            'name' => ['en' => 'Kingdom Tower Station', 'ar' => 'محطة برج المملكة'],
            'lat' => 24.7113,
            'lng' => 46.6745,
            'range_meters' => 500,
            'order' => 1,
        ]);

        Stop::create([
            'route_id' => $route->id,
            'name' => ['en' => 'Faisaliah Tower Station', 'ar' => 'محطة الفيصلية'],
            'lat' => 24.6908,
            'lng' => 46.6841,
            'range_meters' => 500,
            'order' => 2,
        ]);

        PublicBusSchedule::create([
            'route_id' => $route->id,
            'driver_id' => $driverUser->id,
            'vehicle_id' => $bus->id,
            'departure_time' => '09:00:00',
            'arrival_time' => '09:45:00',
            'fare' => 15.00,
            'days_of_week' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'is_active' => true,
        ]);

        $school1 = School::create([
            'name' => ['en' => 'Al-Falah International School', 'ar' => 'مدارس الفلاح العالمية'],
            'lat' => 24.7550,
            'lng' => 46.6950,
        ]);

        $school2 = School::create([
            'name' => ['en' => 'Manarat Al-Riyadh School', 'ar' => 'مدارس منارات الرياض'],
            'lat' => 24.7136,
            'lng' => 46.6753,
        ]);

        $packageMonthly = SchoolPackage::create([
            'name' => ['en' => 'Monthly Package', 'ar' => 'الباقة الشهرية'],
            'description' => ['en' => '30 days subscription', 'ar' => 'اشتراك لمدة 30 يوم'],
            'price' => 350.00,
            'duration_days' => 30,
            'is_active' => true,
        ]);
        $packageTerm = SchoolPackage::create([
            'name' => ['en' => 'Term Package', 'ar' => 'باقة الفصل الدراسي'],
            'description' => ['en' => '90 days subscription', 'ar' => 'اشتراك لمدة 90 يوم'],
            'price' => 950.00,
            'duration_days' => 90,
            'is_active' => true,
        ]);
        
        $school1->packages()->attach([$packageMonthly->id, $packageTerm->id]);
        $school2->packages()->attach([$packageMonthly->id]);

        // Add riders
        $riderRole = Role::findByName('rider', 'web');
        
        // Rider 1
        $customer1 = User::create([
            'name' => 'Test Rider 1',
            'national_id' => '1111111111',
            'gender' => 'male',
            'mobile' => '966896532471',
            'password' => Hash::make('896532471'),
            'mobile_verified_at' => now(),
        ]);
        $customer1->assignRole($riderRole);
        $customer1->wallet()->create(['balance' => 100.00]);

        // Rider 2
        $customer2 = User::create([
            'name' => 'Test Rider 2',
            'national_id' => '2222222222',
            'gender' => 'female',
            'mobile' => '966963785412',
            'password' => Hash::make('963785412'),
            'mobile_verified_at' => now(),
        ]);
        $customer2->assignRole($riderRole);
        $customer2->wallet()->create(['balance' => 150.00]);

        // Rider 3
        $customer3 = User::create([
            'name' => 'Test Rider 3',
            'national_id' => '3333333333',
            'gender' => 'male',
            'mobile' => '966596874526',
            'password' => Hash::make('596874526'),
            'mobile_verified_at' => now(),
        ]);
        $customer3->assignRole($riderRole);
        $customer3->wallet()->create(['balance' => 200.00]);

        // Add comprehensive public buses with schedules across times and cities (same driver and vehicle)
        $this->createComprehensivePublicBuses($driverUser, $bus);
        
        // Add private services for the same vehicle/driver
        $this->createPrivateServices($bus);
    }

    private function createComprehensivePublicBuses($driver, $vehicle)
    {
        // Use the same driver and vehicle for all public routes
        $publicDrivers = [$driver];
        $publicVehicles = [$vehicle];

        // Create comprehensive routes between main cities
        $this->createMainCityRoutes($publicDrivers, $publicVehicles);
    }

    private function createMainCityRoutes($drivers, $vehicles)
    {
        // Main cities in KSA
        $mainCities = [
            ['name' => ['ar' => 'الرياض', 'en' => 'Riyadh'], 'lat' => 24.7136, 'lng' => 46.6753],
            ['name' => ['ar' => 'جدة', 'en' => 'Jeddah'], 'lat' => 21.4858, 'lng' => 39.1925],
            ['name' => ['ar' => 'مكة المكرمة', 'en' => 'Makkah'], 'lat' => 21.3891, 'lng' => 39.8579],
            ['name' => ['ar' => 'المدينة المنورة', 'en' => 'Madinah'], 'lat' => 24.5247, 'lng' => 39.5692],
            ['name' => ['ar' => 'الدمام', 'en' => 'Dammam'], 'lat' => 26.4207, 'lng' => 50.0888],
            ['name' => ['ar' => 'الخبر', 'en' => 'Khobar'], 'lat' => 26.2172, 'lng' => 50.1971],
            ['name' => ['ar' => 'الظهران', 'en' => 'Dhahran'], 'lat' => 26.2361, 'lng' => 50.1033],
            ['name' => ['ar' => 'الطائف', 'en' => 'Taif'], 'lat' => 21.2703, 'lng' => 40.4158],
            ['name' => ['ar' => 'بريدة', 'en' => 'Buraydah'], 'lat' => 26.3260, 'lng' => 43.9750],
            ['name' => ['ar' => 'تبوك', 'en' => 'Tabuk'], 'lat' => 28.3998, 'lng' => 36.5700],
        ];

        $routeIndex = 0;
        $driverIndex = 0;
        $vehicleIndex = 0;

        // إنشاء مسارات بين كل مدينتين
        for ($i = 0; $i < count($mainCities); $i++) {
            for ($j = $i + 1; $j < count($mainCities); $j++) {
                $fromCity = $mainCities[$i];
                $toCity = $mainCities[$j];
                
                $route = PublicBusRoute::create([
                    'name' => [
                        'ar' => $fromCity['name']['ar'] . ' - ' . $toCity['name']['ar'],
                        'en' => $fromCity['name']['en'] . ' - ' . $toCity['name']['en']
                    ],
                    'start_point_name' => $fromCity['name'],
                    'end_point_name' => $toCity['name'],
                    'range_km' => $this->calculateDistance($fromCity['lat'], $fromCity['lng'], $toCity['lat'], $toCity['lng']) / 1000,
                    'is_active' => true,
                ]);

                // Create route stops
                Stop::create([
                    'route_id' => $route->id,
                    'name' => [
                        'ar' => 'محطة ' . $fromCity['name']['ar'] . ' المركزية',
                        'en' => $fromCity['name']['en'] . ' Central Station'
                    ],
                    'lat' => $fromCity['lat'],
                    'lng' => $fromCity['lng'],
                    'range_meters' => 2000,
                    'order' => 1,
                ]);

                Stop::create([
                    'route_id' => $route->id,
                    'name' => [
                        'ar' => 'محطة ' . $toCity['name']['ar'] . ' المركزية',
                        'en' => $toCity['name']['en'] . ' Central Station'
                    ],
                    'lat' => $toCity['lat'],
                    'lng' => $toCity['lng'],
                    'range_meters' => 2000,
                    'order' => 2,
                ]);

                // Create comprehensive schedules (every 2 hours from 6 AM to 10 PM)
                $this->createComprehensiveSchedules($route, $drivers[$driverIndex], $vehicles[$vehicleIndex], $route->range_km);

                $driverIndex = ($driverIndex + 1) % count($drivers);
                $vehicleIndex = ($vehicleIndex + 1) % count($vehicles);
            }
        }
    }

    private function createComprehensiveSchedules($route, $driver, $vehicle, $distanceKm)
    {
        // Calculate trip duration (1 hour per 100 km + 1 extra hour)
        $durationHours = max(2, round($distanceKm / 100) + 1);
        
        // Create schedules every 2 hours from 6 AM to 10 PM
        for ($hour = 6; $hour <= 22; $hour += 2) {
            $departureTime = sprintf('%02d:00:00', $hour);
            $arrivalHour = $hour + $durationHours;
            
            // Handle midnight wrap-around
            if ($arrivalHour >= 24) {
                $arrivalHour = $arrivalHour - 24;
            }
            
            $arrivalTime = sprintf('%02d:00:00', $arrivalHour);
            
            // Calculate the price (15 SAR per 100 km + 30 SAR base)
            $basePrice = 30;
            $distancePrice = round(($distanceKm / 100) * 15);
            $fare = $basePrice + $distancePrice;

            PublicBusSchedule::create([
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'fare' => $fare,
                'days_of_week' => ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'is_active' => true,
            ]);
        }
    }

    private function createPrivateServices($vehicle)
    {
        // Create private services for drivers
        $amenities = [
            ['name' => ['ar' => 'واي فاي مجاني', 'en' => 'Free WiFi'], 'icon' => 'wifi'],
            ['name' => ['ar' => 'تكييف قوي', 'en' => 'Strong AC'], 'icon' => 'snowflake'],
            ['name' => ['ar' => 'شحن سريع للهاتف', 'en' => 'Fast Phone Charging'], 'icon' => 'battery'],
            ['name' => ['ar' => 'ماء مجاني', 'en' => 'Free Water'], 'icon' => 'droplet'],
            ['name' => ['ar' => 'تلفزيون ذكي', 'en' => 'Smart TV'], 'icon' => 'tv'],
            ['name' => ['ar' => 'مقاعد فاخرة', 'en' => 'Luxury Seats'], 'icon' => 'sofa'],
            ['name' => ['ar' => 'خدمة VIP', 'en' => 'VIP Service'], 'icon' => 'crown'],
            ['name' => ['ar' => 'وجبات خفيفة', 'en' => 'Snacks'], 'icon' => 'coffee'],
            ['name' => ['ar' => 'موسيقى هادئة', 'en' => 'Soft Music'], 'icon' => 'music'],
            ['name' => ['ar' => 'مرشد سياحي', 'en' => 'Tour Guide'], 'icon' => 'map'],
        ];

        foreach ($amenities as $amenityData) {
            Amenity::create($amenityData);
        }

        // Attach a random set of amenities to the driver's current vehicle
        $randomAmenities = Amenity::inRandomOrder()->take(rand(4, 6))->get();
        foreach ($randomAmenities as $amenity) {
            $vehicle->amenities()->attach($amenity->id, [
                'price' => rand(10, 50)
            ]);
        }
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        // Calculate distance using Haversine formula
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
} 