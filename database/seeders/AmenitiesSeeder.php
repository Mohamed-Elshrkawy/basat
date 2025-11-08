<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitiesSeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            [
                'name' => [
                    'ar' => 'WiFi',
                    'en' => 'WiFi'
                ],
                'icon' => 'heroicon-o-wifi',
                'description' => [
                    'ar' => 'إنترنت مجاني عالي السرعة طوال الرحلة',
                    'en' => 'Free high-speed internet throughout the journey'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'تكييف',
                    'en' => 'Air Conditioning'
                ],
                'icon' => 'heroicon-o-sparkles',
                'description' => [
                    'ar' => 'تكييف هواء مركزي للراحة التامة',
                    'en' => 'Central air conditioning for maximum comfort'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مقاعد مريحة',
                    'en' => 'Comfortable Seats'
                ],
                'icon' => 'heroicon-o-squares-2x2',
                'description' => [
                    'ar' => 'مقاعد واسعة ومريحة مع مساحة للأرجل',
                    'en' => 'Spacious and comfortable seats with legroom'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'USB للشحن',
                    'en' => 'USB Charging'
                ],
                'icon' => 'heroicon-o-bolt',
                'description' => [
                    'ar' => 'منافذ USB لشحن الأجهزة الإلكترونية',
                    'en' => 'USB ports for charging electronic devices'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'شاشات ترفيه',
                    'en' => 'Entertainment Screens'
                ],
                'icon' => 'heroicon-o-tv',
                'description' => [
                    'ar' => 'شاشات فردية مع أفلام ومسلسلات',
                    'en' => 'Individual screens with movies and series'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مياه ومرطبات',
                    'en' => 'Water & Refreshments'
                ],
                'icon' => 'heroicon-o-beaker',
                'description' => [
                    'ar' => 'مياه ومشروبات خفيفة مجانية',
                    'en' => 'Free water and light beverages'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'دورة مياه',
                    'en' => 'Restroom'
                ],
                'icon' => 'heroicon-o-home',
                'description' => [
                    'ar' => 'دورة مياه نظيفة ومجهزة',
                    'en' => 'Clean and equipped restroom'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'أمان متقدم',
                    'en' => 'Advanced Safety'
                ],
                'icon' => 'heroicon-o-shield-check',
                'description' => [
                    'ar' => 'أنظمة أمان وسلامة متطورة',
                    'en' => 'Advanced safety and security systems'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مساحة للأمتعة',
                    'en' => 'Luggage Space'
                ],
                'icon' => 'heroicon-o-briefcase',
                'description' => [
                    'ar' => 'مساحة واسعة وآمنة للأمتعة',
                    'en' => 'Spacious and secure luggage storage'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'إضاءة للقراءة',
                    'en' => 'Reading Light'
                ],
                'icon' => 'heroicon-o-light-bulb',
                'description' => [
                    'ar' => 'إضاءة فردية لكل مقعد',
                    'en' => 'Individual reading light for each seat'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'Bluetooth Audio',
                    'en' => 'Bluetooth Audio'
                ],
                'icon' => 'heroicon-o-speaker-wave',
                'description' => [
                    'ar' => 'نظام صوتي بلوتوث للاستماع الشخصي',
                    'en' => 'Bluetooth audio system for personal listening'
                ],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'طاولة قابلة للطي',
                    'en' => 'Foldable Table'
                ],
                'icon' => 'heroicon-o-table-cells',
                'description' => [
                    'ar' => 'طاولة صغيرة قابلة للطي للعمل أو الأكل',
                    'en' => 'Small foldable table for work or dining'
                ],
                'is_active' => true,
            ],
        ];

        foreach ($amenities as $amenity) {
            Amenity::create($amenity);
        }

        $this->command->info('✅ تم إنشاء ' . count($amenities) . ' وسيلة بنجاح!');
    }
}
