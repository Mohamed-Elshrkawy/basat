<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\PublicBusSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdatePublicBusTripsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the public bus trips with added distance and duration data
        $publicBusTrips = Trip::where('type', 'public_bus')
            ->whereNull('distance')
            ->whereNull('duration')
            ->get();

        foreach ($publicBusTrips as $trip) {
            // Calculate the distance and duration based on the coordinates
            $distance = null;
            $duration = null;
            
            if ($trip->pickup_lat && $trip->pickup_lng && $trip->dropoff_lat && $trip->dropoff_lng) {
                $distance = $this->calculateDistance(
                    $trip->pickup_lat, $trip->pickup_lng,
                    $trip->dropoff_lat, $trip->dropoff_lng
                );
                // Estimate the duration based on the distance (average speed 40 km/hour)
                $duration = $distance ? round(($distance / 40) * 60) : null;
            } else {
                // If the coordinates are not available, use dummy values
                $distance = rand(50, 300) / 10; // 5.0 to 30.0 km
                $duration = rand(15, 90); // 15 to 90 minutes
            }
            
            $trip->update([
                'distance' => $distance,
                'duration' => $duration,
            ]);
        }

        $this->command->info("Updated {$publicBusTrips->count()} public bus trips with distance and duration data.");
    }

    /**
     * Calculate the distance between two points using the Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return round($earthRadius * $c, 2);
    }
}
