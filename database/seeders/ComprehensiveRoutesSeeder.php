<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicBusRoute;
use App\Models\Stop;
use App\Models\PublicBusSchedule;
use App\Models\City;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ComprehensiveRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a single driver and vehicle for all routes
        [$driverUser, $vehicle] = $this->ensureSingleDriverAndVehicle();

        // Create comprehensive routes between all cities
        $this->createComprehensiveRoutes($driverUser, $vehicle);
    }

    private function ensureSingleDriverAndVehicle(): array
    {
        $driverRole = Role::findByName('driver', 'web');

        $driverUser = User::whereHas('roles', function($q) use ($driverRole) {
            $q->where('name', $driverRole->name);
        })->first();

        if (!$driverUser) {
            $driverUser = User::create([
                'name' => 'Single Driver',
                'national_id' => 'ROUTE000001',
                'gender' => 'male',
                'mobile' => '966500000200',
                'password' => Hash::make('password'),
                'mobile_verified_at' => now(),
            ]);
            $driverUser->assignRole($driverRole);
            $driverUser->driverProfile()->create([
                'availability_status' => 'available',
                'bio' => 'Single driver for all comprehensive routes',
            ]);
            $driverUser->wallet()->create(['balance' => 0]);
        }

        $vehicle = Vehicle::where('driver_id', $driverUser->id)->first();
        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'driver_id' => $driverUser->id,
                'brand' => 'Mercedes',
                'model' => 'Tourismo',
                'plate_number' => 'ROUTE001',
                'seat_count' => 50,
                'type' => 'bus',
                'is_active' => true,
            ]);
        }

        return [$driverUser, $vehicle];
    }

    private function createComprehensiveRoutes($driverUser, $vehicle)
    {
        $cities = City::active()->get();
        
        // Create routes between all cities
        foreach ($cities as $fromCity) {
            foreach ($cities as $toCity) {
                if ($fromCity->id !== $toCity->id) {
                    $this->createRouteBetweenCities($fromCity, $toCity, $driverUser, $vehicle);
                }
            }
        }
    }

    private function createRouteBetweenCities($fromCity, $toCity, $driverUser, $vehicle)
    {
        // Create the route name
        $fromCityName = $fromCity->getTranslation('name', 'en');
        $toCityName = $toCity->getTranslation('name', 'en');
        $fromCityNameAr = $fromCity->getTranslation('name', 'ar');
        $toCityNameAr = $toCity->getTranslation('name', 'ar');
        
        $routeName = [
            'en' => $fromCityName . ' - ' . $toCityName,
            'ar' => $fromCityNameAr . ' - ' . $toCityNameAr
        ];

        // Check if the route exists
        $existingRoute = PublicBusRoute::where('name->en', $routeName['en'])->first();
        if ($existingRoute) {
            return; // The route already exists
        }

        // Calculate the approximate distance
        $distance = $this->calculateDistance(
            $fromCity->lat, $fromCity->lng,
            $toCity->lat, $toCity->lng
        );

        // Create the route
        $route = PublicBusRoute::create([
            'name' => $routeName,
            'start_point_name' => [
                'en' => $fromCityName,
                'ar' => $fromCityNameAr
            ],
            'end_point_name' => [
                'en' => $toCityName,
                'ar' => $toCityNameAr
            ],
            'range_km' => max(50, round($distance / 1000)), // at least 50 km
            'is_active' => true,
        ]);

        // Create stops for the route
        $this->createStopsForRoute($route, $fromCity, $toCity);

        // Create comprehensive schedules for the route
        $this->createSchedulesForRoute($route, $driverUser, $vehicle, $distance);
    }

    private function createStopsForRoute($route, $fromCity, $toCity)
    {
        $fromCityName = $fromCity->getTranslation('name', 'en');
        $toCityName = $toCity->getTranslation('name', 'en');
        $fromCityNameAr = $fromCity->getTranslation('name', 'ar');
        $toCityNameAr = $toCity->getTranslation('name', 'ar');
        
        // Start stop
        Stop::create([
            'route_id' => $route->id,
            'name' => [
                'en' => $fromCityName . ' Central Station',
                'ar' => 'محطة ' . $fromCityNameAr . ' المركزية'
            ],
            'lat' => $fromCity->lat,
            'lng' => $fromCity->lng,
            'range_meters' => 2000, // 2 km
            'order' => 1,
        ]);

        // End stop
        Stop::create([
            'route_id' => $route->id,
            'name' => [
                'en' => $toCityName . ' Central Station',
                'ar' => 'محطة ' . $toCityNameAr . ' المركزية'
            ],
            'lat' => $toCity->lat,
            'lng' => $toCity->lng,
            'range_meters' => 2000, // 2 km
            'order' => 2,
        ]);
    }

    private function createSchedulesForRoute($route, $driverUser, $vehicle, $distance)
    {
        // Calculate the duration of the trip (1 hour per 100 km + 1 extra hour)
        $durationHours = max(1, round($distance / 100000) + 1); // 100 كم = 1 ساعة
        
        // Create comprehensive schedules for each hour from 6 AM to 10 PM
        for ($hour = 6; $hour <= 22; $hour++) {
            $departureTime = sprintf('%02d:00:00', $hour);
            $arrivalHour = $hour + $durationHours;
            
            // Handle midnight wrap-around
            if ($arrivalHour >= 24) {
                $arrivalHour = $arrivalHour - 24;
            }
            
            $arrivalTime = sprintf('%02d:00:00', $arrivalHour);
            
        // Calculate fare (10 SAR per 100 km + 50 SAR base)
            $basePrice = 50;
            $distancePrice = round(($distance / 100000) * 10);
            $fare = $basePrice + $distancePrice;

            // Create the schedule
            PublicBusSchedule::create([
                'route_id' => $route->id,
                'driver_id' => $driverUser->id,
                'vehicle_id' => $vehicle->id,
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'fare' => $fare,
                'days_of_week' => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'is_active' => true,
            ]);
        }
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        // Calculate the distance using the Haversine formula
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