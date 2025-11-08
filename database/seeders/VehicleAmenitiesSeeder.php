<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;
use App\Models\Amenity;

class VehicleAmenitiesSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = Vehicle::all();
        $amenities = Amenity::all();

        foreach ($vehicles as $vehicle) {
            // Add random amenities to each vehicle
            $randomAmenities = $amenities->random(rand(2, 4));
            foreach ($randomAmenities as $amenity) {
                $vehicle->amenities()->attach($amenity->id, [
                    'price' => rand(5, 20) // Random price between 5-20 SAR
                ]);
            }
        }
    }
}
