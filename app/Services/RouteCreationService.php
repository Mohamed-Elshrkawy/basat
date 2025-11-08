<?php

namespace App\Services;

use App\Models\City;
use App\Models\PublicBusRoute;
use App\Models\Stop;

class RouteCreationService
{
    /**
     * Create route between two cities
     */
    public function createRouteBetweenCities(City $fromCity, City $toCity): PublicBusRoute
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

    /**
     * Create stops for a route
     */
    private function createStopsForRoute(PublicBusRoute $route, City $fromCity, City $toCity): void
    {
        // Start stop
        Stop::create([
            'route_id' => $route->id,
            'name' => [
                'ar' => 'محطة ' . $fromCity->getTranslation('name', 'ar') . ' المركزية',
                'en' => $fromCity->getTranslation('name', 'en') . ' Central Station'
            ],
            'lat' => $fromCity->lat,
            'lng' => $fromCity->lng,
            'range_meters' => 2000,
            'order' => 1,
        ]);

        // End stop
        Stop::create([
            'route_id' => $route->id,
            'name' => [
                'ar' => 'محطة ' . $toCity->getTranslation('name', 'ar') . ' المركزية',
                'en' => $toCity->getTranslation('name', 'en') . ' Central Station'
            ],
            'lat' => $toCity->lat,
            'lng' => $toCity->lng,
            'range_meters' => 2000,
            'order' => 2,
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    /**
     * Create routes between all cities
     */
    public function createRoutesBetweenAllCities(): int
    {
        $cities = City::where('is_active', true)->get();
        $routesCreated = 0;

        foreach ($cities as $fromCity) {
            foreach ($cities as $toCity) {
                if ($fromCity->id !== $toCity->id) {
                    $this->createRouteBetweenCities($fromCity, $toCity);
                    $routesCreated++;
                }
            }
        }

        return $routesCreated;
    }
}
