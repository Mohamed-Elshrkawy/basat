<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            StaticPageSeeder::class,
            FaqSeeder::class,
            CitySeeder::class,
            VehicleDataSeeder::class,
            StopsSeeder::class,
            AmenitiesSeeder::class,
            DriversSeeder::class,
            RoutesSeeder::class,
            //ComprehensiveRoutesSeeder::class, // Enabled after modifying it for a single driver
            //ComprehensivePublicBusTripsSeeder::class, // Enabled after modifying it for a single driver
            // FrequentRoutesSeeder::class,
            // WeekendAndNightTripsSeeder::class,
            //TestDataSeeder::class,
        ]);
    }
}
