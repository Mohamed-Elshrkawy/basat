<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PublicBusRoute;
use App\Models\PublicBusSchedule;
use App\Models\User;
use App\Models\Vehicle;

class WeekendAndNightTripsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting to create weekend and night trips...');
        
        // Get all active routes
        $routes = PublicBusRoute::where('is_active', true)->get();
        
        foreach ($routes as $route) {
            $this->createWeekendAndNightTrips($route);
        }
        
        $this->command->info('Weekend and night trips created successfully!');
    }

    private function createWeekendAndNightTrips($route)
    {
        // Get available driver and vehicle
        $driver = User::whereHas('roles', function($q) {
            $q->where('name', 'driver');
        })->where('name', 'like', 'سائق باص عام%')->first();

        $vehicle = Vehicle::where('type', 'bus')->first();

        if (!$driver || !$vehicle) {
            return;
        }

        // Calculate trip duration
        $durationHours = max(1, round($route->range_km / 100) + 1);

        // Create weekend trips (Friday and Saturday)
        $this->createWeekendTrips($route, $driver, $vehicle, $durationHours);
        
        // Create additional night trips
        $this->createNightTrips($route, $driver, $vehicle, $durationHours);
    }

    private function createWeekendTrips($route, $driver, $vehicle, $durationHours)
    {
        // Weekend trips - every 2 hours from 8 AM to 8 PM
        for ($hour = 8; $hour <= 20; $hour += 2) {
            $departureTime = sprintf('%02d:00:00', $hour);
            $arrivalHour = $hour + $durationHours;
            
            if ($arrivalHour >= 24) {
                $arrivalHour = $arrivalHour - 24;
            }
            
            $arrivalTime = sprintf('%02d:00:00', $arrivalHour);
            
            // Calculate the price (20% increase in weekend)
            $basePrice = 50;
            $distancePrice = round(($route->range_km / 100) * 10);
            $fare = round(($basePrice + $distancePrice) * 1.2);

            // Check if the schedule exists
            $existingSchedule = PublicBusSchedule::where('route_id', $route->id)
                ->where('departure_time', $departureTime)
                ->whereJsonContains('days_of_week', 'friday')
                ->first();

            if (!$existingSchedule) {
                PublicBusSchedule::create([
                    'route_id' => $route->id,
                    'driver_id' => $driver->id,
                    'vehicle_id' => $vehicle->id,
                    'departure_time' => $departureTime,
                    'arrival_time' => $arrivalTime,
                    'fare' => $fare,
                    'days_of_week' => ['friday', 'saturday'],
                    'is_active' => true,
                ]);
            }
        }
    }

    private function createNightTrips($route, $driver, $vehicle, $durationHours)
    {
        // Night trips - every 3 hours from 11 PM to 5 AM
        $nightHours = [23, 2, 5];
        
        foreach ($nightHours as $hour) {
            $departureTime = sprintf('%02d:00:00', $hour);
            $arrivalHour = $hour + $durationHours;
            
            if ($arrivalHour >= 24) {
                $arrivalHour = $arrivalHour - 24;
            }
            
            $arrivalTime = sprintf('%02d:00:00', $arrivalHour);
            
            // Calculate the price (30% increase in night)
            $basePrice = 50;
            $distancePrice = round(($route->range_km / 100) * 10);
            $fare = round(($basePrice + $distancePrice) * 1.3);

            // Check if the schedule exists
            $existingSchedule = PublicBusSchedule::where('route_id', $route->id)
                ->where('departure_time', $departureTime)
                ->whereJsonContains('days_of_week', 'monday')
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
}

