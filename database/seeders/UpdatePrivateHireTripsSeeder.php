<?php

namespace Database\Seeders;

use App\Models\Trip;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePrivateHireTripsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the private hire trips with added distance and duration data
        $privateHireTrips = Trip::where('type', 'private_hire')
            ->whereNull('distance')
            ->whereNull('duration')
            ->get();

        foreach ($privateHireTrips as $trip) {
            // Add a random distance between 5-50 km
            $distance = rand(50, 500) / 10; // 5.0 to 50.0 km
            
            // Add a random duration between 15-120 minutes
            $duration = rand(15, 120);
            
            $trip->update([
                'distance' => $distance,
                'duration' => $duration,
            ]);
        }

        $this->command->info("Updated {$privateHireTrips->count()} private hire trips with distance and duration data.");
    }
}
