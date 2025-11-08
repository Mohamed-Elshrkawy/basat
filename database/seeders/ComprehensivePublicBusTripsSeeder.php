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

class ComprehensivePublicBusTripsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting comprehensive public bus trips seeding...');
        
        // Get a single driver and vehicle for all trips
        [$driver, $vehicle] = $this->ensureSingleDriverAndVehicle();
        
        // Create comprehensive trips between all cities
        $this->createComprehensivePublicBusTrips($driver, $vehicle);
        
        $this->command->info('Comprehensive public bus trips created successfully!');
    }

    private function ensureSingleDriverAndVehicle()
    {
        $driverRole = Role::findByName('driver', 'web');

        // Try to use the first user with a driver role and their vehicle
        $driver = User::whereHas('roles', function($q) use ($driverRole) {
            $q->where('name', $driverRole->name);
        })->first();

        if (!$driver) {
            $driver = User::create([
                'name' => 'Single Driver',
                'national_id' => 'PUBLIC000001',
                'gender' => 'male',
                'mobile' => '966500000100',
                'password' => Hash::make('password'),
                'mobile_verified_at' => now(),
            ]);
            $driver->assignRole($driverRole);
            $driver->driverProfile()->create([
                'availability_status' => 'available',
                'bio' => 'Single driver for all public trips',
            ]);
            $driver->wallet()->create(['balance' => 0]);
        }

        $vehicle = Vehicle::where('driver_id', $driver->id)->first();
        if (!$vehicle) {
            $vehicle = Vehicle::create([
                'driver_id' => $driver->id,
                'brand' => 'Mercedes',
                'model' => 'Tourismo',
                'plate_number' => 'PUB001',
                'seat_count' => 50,
                'type' => 'bus',
                'is_active' => true,
            ]);
        }

        return [$driver, $vehicle];
    }

    private function createComprehensivePublicBusTrips($driver, $vehicle)
    {
        $cities = City::active()->get();

        if ($cities->isEmpty()) {
            $this->command->warn('No active cities in the database');
            return;
        }

        $routeCount = 0;

        // Create routes between all cities
        foreach ($cities as $fromCity) {
            foreach ($cities as $toCity) {
                if ($fromCity->id !== $toCity->id) {
                    $route = $this->createRouteBetweenCities($fromCity, $toCity);
                    if ($route) {
                        $this->createComprehensiveSchedulesForRoute($route, $driver, $vehicle);
                        $routeCount++;
                    }
                }
            }
        }

        $this->command->info("Created {$routeCount} routes with comprehensive trips");
    }

    private function createRouteBetweenCities($fromCity, $toCity)
    {
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
            return $existingRoute;
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

        return $route;
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

    private function createComprehensiveSchedulesForRoute($route, $driver, $vehicle)
    {
        // Calculate the duration of the trip (1 hour per 100 km + 1 extra hour)
        $durationHours = max(1, round($route->range_km / 100) + 1);
        
        // Create comprehensive schedules
        // Morning trips: every hour from 6 AM to 12 PM
        for ($hour = 6; $hour <= 12; $hour++) {
            $this->createScheduleForTime($route, $driver, $vehicle, $hour, $durationHours);
        }
        
        // Afternoon trips: every 2 hours from 2 PM to 6 PM
        for ($hour = 14; $hour <= 18; $hour += 2) {
            $this->createScheduleForTime($route, $driver, $vehicle, $hour, $durationHours);
        }
        
        // Evening trips: every hour from 7 PM to 10 PM
        for ($hour = 19; $hour <= 22; $hour++) {
            $this->createScheduleForTime($route, $driver, $vehicle, $hour, $durationHours);
        }
    }

    private function createScheduleForTime($route, $driver, $vehicle, $departureHour, $durationHours)
    {
        $departureTime = sprintf('%02d:00:00', $departureHour);
        $arrivalHour = $departureHour + $durationHours;
        
        // Handle midnight wrap-around
        if ($arrivalHour >= 24) {
            $arrivalHour = $arrivalHour - 24;
        }
        
        $arrivalTime = sprintf('%02d:00:00', $arrivalHour);
        
        // Calculate fare (10 SAR per 100 km + 50 SAR base)
        $basePrice = 50;
        $distancePrice = round(($route->range_km / 100) * 10);
        $fare = $basePrice + $distancePrice;

        // Ensure schedule uniqueness per route and departure time
        $existingSchedule = PublicBusSchedule::where('route_id', $route->id)
            ->where('departure_time', $departureTime)
            ->first();

        if (!$existingSchedule) {
            PublicBusSchedule::create([
                'route_id' => $route->id,
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'departure_time' => $departureTime,
                'arrival_time' => $arrivalTime,
                'fare' => $fare,
                'days_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
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

