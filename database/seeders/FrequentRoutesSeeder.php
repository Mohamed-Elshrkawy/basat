<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicBusRoute;
use App\Models\PublicBusSchedule;
use App\Models\City;
use App\Models\User;
use App\Models\Vehicle;

class FrequentRoutesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to create frequent trips for main routes...');
        
        // Main routes that need frequent trips
        $mainRoutes = [
            ['from' => 'الرياض', 'to' => 'جدة'],
            ['from' => 'الرياض', 'to' => 'الدمام'],
            ['from' => 'الرياض', 'to' => 'مكة المكرمة'],
            ['from' => 'الرياض', 'to' => 'المدينة المنورة'],
            ['from' => 'جدة', 'to' => 'مكة المكرمة'],
            ['from' => 'جدة', 'to' => 'المدينة المنورة'],
            ['from' => 'الدمام', 'to' => 'الخبر'],
            ['from' => 'الدمام', 'to' => 'الظهران'],
        ];

        foreach ($mainRoutes as $routeInfo) {
            $this->createFrequentTripsForRoute($routeInfo['from'], $routeInfo['to']);
        }
        
        $this->command->info('Frequent trips created successfully.');
    }

    private function createFrequentTripsForRoute($fromCityNameAr, $toCityNameAr)
    {
        // Find the route by Arabic names
        $route = PublicBusRoute::where('name->ar', 'like', "%{$fromCityNameAr}%")
            ->where('name->ar', 'like', "%{$toCityNameAr}%")
            ->first();

        if (!$route) {
            $this->command->warn("Route not found: {$fromCityNameAr} - {$toCityNameAr}");
            return;
        }

        // Get available driver and vehicle
        $driver = User::whereHas('roles', function($q) {
            $q->where('name', 'driver');
        })->where('name', 'like', 'سائق باص عام%')
        ->whereDoesntHave('schedules', function($q) use ($route) {
            $q->where('route_id', $route->id);
        })->first();

        if (!$driver) {
            $driver = User::whereHas('roles', function($q) {
                $q->where('name', 'driver');
            })->where('name', 'like', 'سائق باص عام%')->first();
        }

        $vehicle = Vehicle::where('driver_id', $driver->id)->first();

        if (!$driver || !$vehicle) {
            $this->command->warn("No available driver or vehicle for route: {$fromCityNameAr} - {$toCityNameAr}");
            return;
        }

        // Calculate trip duration
        $durationHours = max(1, round($route->range_km / 100) + 1);

        // Create frequent schedules every 30 minutes from 6 AM to 10 PM
        for ($hour = 6; $hour <= 22; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $this->createFrequentSchedule($route, $driver, $vehicle, $hour, $minute, $durationHours);
            }
        }

        // Night trips (hour 23 once, and every 2 hours from 1 AM to 5 AM)
        for ($hour = 23; $hour <= 23; $hour++) {
            $this->createFrequentSchedule($route, $driver, $vehicle, $hour, 0, $durationHours);
        }
        
        for ($hour = 1; $hour <= 5; $hour += 2) {
            $this->createFrequentSchedule($route, $driver, $vehicle, $hour, 0, $durationHours);
        }

        $this->command->info("Frequent trips created for route: {$fromCityNameAr} - {$toCityNameAr}");
    }

    private function createFrequentSchedule($route, $driver, $vehicle, $hour, $minute, $durationHours)
    {
        $departureTime = sprintf('%02d:%02d:00', $hour, $minute);
        $arrivalHour = $hour + $durationHours;
        $arrivalMinute = $minute;
        
        // Handle midnight wrap-around
        if ($arrivalHour >= 24) {
            $arrivalHour = $arrivalHour - 24;
        }
        
        $arrivalTime = sprintf('%02d:%02d:00', $arrivalHour, $arrivalMinute);
        
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
}

