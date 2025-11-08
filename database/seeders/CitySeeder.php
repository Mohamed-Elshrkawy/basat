<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            [
                'name' => [
                    'ar' => 'الرياض',
                    'en' => 'Riyadh'
                ],
                'lat' => 24.7136,
                'lng' => 46.6753,
            ],
            [
                'name' => [
                    'ar' => 'جدة',
                    'en' => 'Jeddah'
                ],
                'lat' => 21.4858,
                'lng' => 39.1925,
            ],
            [
                'name' => [
                    'ar' => 'مكة المكرمة',
                    'en' => 'Makkah'
                ],
                'lat' => 21.3891,
                'lng' => 39.8579,
            ],
            [
                'name' => [
                    'ar' => 'المدينة المنورة',
                    'en' => 'Madinah'
                ],
                'lat' => 24.5247,
                'lng' => 39.5692,
            ],
            [
                'name' => [
                    'ar' => 'الدمام',
                    'en' => 'Dammam'
                ],
                'lat' => 26.4207,
                'lng' => 50.0888,
            ],
            [
                'name' => [
                    'ar' => 'الخبر',
                    'en' => 'Khobar'
                ],
                'lat' => 26.2172,
                'lng' => 50.1971,
            ],
            [
                'name' => [
                    'ar' => 'الظهران',
                    'en' => 'Dhahran'
                ],
                'lat' => 26.2361,
                'lng' => 50.1033,
            ],
            [
                'name' => [
                    'ar' => 'الطائف',
                    'en' => 'Taif'
                ],
                'lat' => 21.2703,
                'lng' => 40.4158,
            ],
            [
                'name' => [
                    'ar' => 'بريدة',
                    'en' => 'Buraydah'
                ],
                'lat' => 26.3260,
                'lng' => 43.9750,
            ],
            [
                'name' => [
                    'ar' => 'تبوك',
                    'en' => 'Tabuk'
                ],
                'lat' => 28.3838,
                'lng' => 36.5550,
            ],
            [
                'name' => [
                    'ar' => 'حائل',
                    'en' => 'Hail'
                ],
                'lat' => 27.5114,
                'lng' => 41.6900,
            ],
            [
                'name' => [
                    'ar' => 'الجبيل',
                    'en' => 'Jubail'
                ],
                'lat' => 27.0174,
                'lng' => 49.6595,
            ],
            [
                'name' => [
                    'ar' => 'ينبع',
                    'en' => 'Yanbu'
                ],
                'lat' => 24.0897,
                'lng' => 38.0619,
            ],
            [
                'name' => [
                    'ar' => 'أبها',
                    'en' => 'Abha'
                ],
                'lat' => 18.2465,
                'lng' => 42.5056,
            ],
            [
                'name' => [
                    'ar' => 'نجران',
                    'en' => 'Najran'
                ],
                'lat' => 17.4917,
                'lng' => 44.1277,
            ],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
