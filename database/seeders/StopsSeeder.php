<?php

namespace Database\Seeders;

use App\Models\Stop;
use Illuminate\Database\Seeder;

class StopsSeeder extends Seeder
{
    public function run(): void
    {
        $stops = [
            // محطات الرياض
            [
                'name' => [
                    'ar' => 'محطة الملك فهد',
                    'en' => 'King Fahd Station'
                ],
                'lat' => 24.7136,
                'lng' => 46.6753,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة العليا',
                    'en' => 'Al Olaya Station'
                ],
                'lat' => 24.6944,
                'lng' => 46.6849,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الملز',
                    'en' => 'Al Malaz Station'
                ],
                'lat' => 24.6877,
                'lng' => 46.7219,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة النخيل',
                    'en' => 'Al Nakheel Station'
                ],
                'lat' => 24.7500,
                'lng' => 46.6500,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الديرة',
                    'en' => 'Al Dirah Station'
                ],
                'lat' => 24.6400,
                'lng' => 46.7150,
                'is_active' => true
            ],

            // محطات جدة
            [
                'name' => [
                    'ar' => 'محطة الكورنيش',
                    'en' => 'Corniche Station'
                ],
                'lat' => 21.5433,
                'lng' => 39.1728,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة البلد',
                    'en' => 'Al Balad Station'
                ],
                'lat' => 21.4858,
                'lng' => 39.1925,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة حي الحمراء',
                    'en' => 'Al Hamra District Station'
                ],
                'lat' => 21.5811,
                'lng' => 39.1570,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الشاطئ',
                    'en' => 'Beach Station'
                ],
                'lat' => 21.6000,
                'lng' => 39.1000,
                'is_active' => true
            ],

            // محطات مكة
            [
                'name' => [
                    'ar' => 'محطة العزيزية',
                    'en' => 'Al Aziziyah Station'
                ],
                'lat' => 21.4247,
                'lng' => 39.8175,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة المسفلة',
                    'en' => 'Al Misfalah Station'
                ],
                'lat' => 21.4190,
                'lng' => 39.8280,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة العمرة',
                    'en' => 'Al Umrah Station'
                ],
                'lat' => 21.4500,
                'lng' => 39.8000,
                'is_active' => true
            ],

            // محطات المدينة المنورة
            [
                'name' => [
                    'ar' => 'محطة قباء',
                    'en' => 'Quba Station'
                ],
                'lat' => 24.4418,
                'lng' => 39.6170,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة العوالي',
                    'en' => 'Al Awali Station'
                ],
                'lat' => 24.4650,
                'lng' => 39.6100,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة القبلتين',
                    'en' => 'Qiblatain Station'
                ],
                'lat' => 24.4800,
                'lng' => 39.5900,
                'is_active' => true
            ],

            // محطات الدمام
            [
                'name' => [
                    'ar' => 'محطة الكورنيش الشمالي',
                    'en' => 'North Corniche Station'
                ],
                'lat' => 26.4367,
                'lng' => 50.1039,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الفيصلية',
                    'en' => 'Al Faisaliyah Station'
                ],
                'lat' => 26.4207,
                'lng' => 50.0888,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الشاطئ الشرقي',
                    'en' => 'East Beach Station'
                ],
                'lat' => 26.4500,
                'lng' => 50.1200,
                'is_active' => true
            ],

            // محطات الخبر
            [
                'name' => [
                    'ar' => 'محطة الراكة',
                    'en' => 'Al Rakah Station'
                ],
                'lat' => 26.2885,
                'lng' => 50.2080,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة العقربية',
                    'en' => 'Al Aqrabiyah Station'
                ],
                'lat' => 26.3000,
                'lng' => 50.2000,
                'is_active' => true
            ],

            // محطات الطائف
            [
                'name' => [
                    'ar' => 'محطة الحوية',
                    'en' => 'Al Hawiyah Station'
                ],
                'lat' => 21.2703,
                'lng' => 40.4158,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الشفا',
                    'en' => 'Al Shafa Station'
                ],
                'lat' => 21.3000,
                'lng' => 40.4500,
                'is_active' => true
            ],

            // محطات أبها
            [
                'name' => [
                    'ar' => 'محطة الجبل الأخضر',
                    'en' => 'Green Mountain Station'
                ],
                'lat' => 18.2164,
                'lng' => 42.5053,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة السودة',
                    'en' => 'Al Soudah Station'
                ],
                'lat' => 18.2741,
                'lng' => 42.3647,
                'is_active' => true
            ],

            // محطات تبوك
            [
                'name' => [
                    'ar' => 'محطة الورد',
                    'en' => 'Al Ward Station'
                ],
                'lat' => 28.3998,
                'lng' => 36.5700,
                'is_active' => true
            ],

            // محطات القصيم
            [
                'name' => [
                    'ar' => 'محطة بريدة المركزية',
                    'en' => 'Buraidah Central Station'
                ],
                'lat' => 26.3260,
                'lng' => 43.9750,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة عنيزة',
                    'en' => 'Unaizah Station'
                ],
                'lat' => 26.0877,
                'lng' => 43.9930,
                'is_active' => true
            ],

            // محطات حائل
            [
                'name' => [
                    'ar' => 'محطة السمراء',
                    'en' => 'Al Samra Station'
                ],
                'lat' => 27.5114,
                'lng' => 41.7208,
                'is_active' => true
            ],

            // محطات الجوف
            [
                'name' => [
                    'ar' => 'محطة سكاكا',
                    'en' => 'Sakaka Station'
                ],
                'lat' => 29.9697,
                'lng' => 40.2064,
                'is_active' => true
            ],

            // محطات نجران
            [
                'name' => [
                    'ar' => 'محطة فيصلية',
                    'en' => 'Faisaliyah Station'
                ],
                'lat' => 17.4924,
                'lng' => 44.1277,
                'is_active' => true
            ],

            // محطات جازان
            [
                'name' => [
                    'ar' => 'محطة الكورنيش الجنوبي',
                    'en' => 'South Corniche Station'
                ],
                'lat' => 16.8892,
                'lng' => 42.5678,
                'is_active' => true
            ],

            // محطات على الطرق السريعة
            [
                'name' => [
                    'ar' => 'محطة الدوادمي - طريق الرياض جدة',
                    'en' => 'Al Dawadmi Station - Riyadh Jeddah Highway'
                ],
                'lat' => 24.5000,
                'lng' => 44.3926,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الرس - طريق القصيم',
                    'en' => 'Al Rass Station - Qassim Highway'
                ],
                'lat' => 25.8694,
                'lng' => 43.4975,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة الخرج',
                    'en' => 'Al Kharj Station'
                ],
                'lat' => 24.1551,
                'lng' => 47.3340,
                'is_active' => true
            ],
            [
                'name' => [
                    'ar' => 'محطة حفر الباطن',
                    'en' => 'Hafar Al Batin Station'
                ],
                'lat' => 28.4327,
                'lng' => 45.9603,
                'is_active' => true
            ],

            // محطة غير نشطة (للاختبار)
            [
                'name' => [
                    'ar' => 'محطة قديمة - صيانة',
                    'en' => 'Old Station - Maintenance'
                ],
                'lat' => 24.0000,
                'lng' => 45.0000,
                'is_active' => false
            ],
        ];

        foreach ($stops as $stop) {
            Stop::create($stop);
        }

        $this->command->info('✅ تم إنشاء ' . count($stops) . ' محطة بنجاح!');
    }
}
