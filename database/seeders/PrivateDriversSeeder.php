<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleAmenity;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\Amenity;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PrivateDriversSeeder extends Seeder
{
    public function run(): void
    {
        // التأكد من وجود Brands و Models و Amenities و Cities
        $brands = Brand::where('is_active', true)->get();
        $amenities = Amenity::all();
        $cities = City::all();

        if ($brands->isEmpty()) {
            $this->command->warn('⚠️ لا توجد علامات تجارية (Brands) نشطة في قاعدة البيانات!');
            return;
        }

        if ($cities->isEmpty()) {
            $this->command->warn('⚠️ لا توجد مدن في قاعدة البيانات!');
            return;
        }

        $privateDrivers = [
            [
                'user' => [
                    'name' => 'سامي طارق المحمدي',
                    'national_id' => '1401234567',
                    'gender' => 'male',
                    'phone' => '0571234567',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق حافلات خاصة متخصص في الرحلات العائلية والسياحية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'مرسيدس',
                    'plate_number' => 'خ ص ص 1111',
                    'seat_count' => 30,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 30.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'شاشات ترفيه' => 20.00,
                    'USB للشحن' => 10.00,
                ],
                'cities' => ['الرياض', 'جدة', 'مكة المكرمة', 'المدينة المنورة'],
            ],
            [
                'user' => [
                    'name' => 'عادل يوسف الشهري',
                    'national_id' => '1402345678',
                    'gender' => 'male',
                    'phone' => '0572345678',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في رحلات الأعمال والمؤتمرات',
                    'availability_status' => 'available',
                    'avg_rating' => 5.0,
                ],
                'vehicle' => [
                    'brand_name' => 'فولفو',
                    'plate_number' => 'خ ص ص 2222',
                    'seat_count' => 25,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 25.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'طاولة قابلة للطي' => 15.00,
                ],
                'cities' => ['الرياض', 'الدمام', 'الخبر'],
            ],
            [
                'user' => [
                    'name' => 'وليد حمد القحطاني',
                    'national_id' => '1403456789',
                    'gender' => 'male',
                    'phone' => '0573456789',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق ممتاز للرحلات الطويلة بين المدن',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'خ ص ص 3333',
                    'seat_count' => 20,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 20.00,
                    'تكييف' => 0.00,
                    'مياه ومرطبات' => 25.00,
                ],
                'cities' => ['جدة', 'مكة المكرمة', 'الطائف'],
            ],
            [
                'user' => [
                    'name' => 'إبراهيم صالح الغامدي',
                    'national_id' => '1404567890',
                    'gender' => 'male',
                    'phone' => '0574567890',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'خبرة 12 سنة في نقل المجموعات السياحية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.7,
                ],
                'vehicle' => [
                    'brand_name' => 'يوتونغ',
                    'plate_number' => 'خ ص ص 4444',
                    'seat_count' => 35,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 15.00,
                    'تكييف' => 0.00,
                    'دورة مياه' => 50.00,
                ],
                'cities' => ['أبها', 'الرياض', 'جدة'],
            ],
            [
                'user' => [
                    'name' => 'ناصر فهد العتيبي',
                    'national_id' => '1405678901',
                    'gender' => 'male',
                    'phone' => '0575678901',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق محترف للفعاليات والحفلات',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'كينغ لونغ',
                    'plate_number' => 'خ ص ص 5555',
                    'seat_count' => 40,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 30.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'شاشات ترفيه' => 25.00,
                    'Bluetooth Audio' => 10.00,
                ],
                'cities' => ['الرياض', 'بريدة'],
            ],
            [
                'user' => [
                    'name' => 'راشد عبدالله الحربي',
                    'national_id' => '1406789012',
                    'gender' => 'male',
                    'phone' => '0576789012',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في نقل الأفواج السياحية الخليجية',
                    'availability_status' => 'on_trip',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'MAN',
                    'plate_number' => 'خ ص ص 6666',
                    'seat_count' => 45,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 25.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'دورة مياه' => 50.00,
                    'مياه ومرطبات' => 30.00,
                ],
                'cities' => ['المدينة المنورة', 'مكة المكرمة', 'جدة'],
            ],
            [
                'user' => [
                    'name' => 'مشاري خالد السبيعي',
                    'national_id' => '1407890123',
                    'gender' => 'male',
                    'phone' => '0577890123',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق موثوق للرحلات الشرقية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.6,
                ],
                'vehicle' => [
                    'brand_name' => 'هيونداي',
                    'plate_number' => 'خ ص ص 7777',
                    'seat_count' => 30,
                    'type' => 'private_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 20.00,
                    'تكييف' => 0.00,
                ],
                'cities' => ['الدمام', 'الخبر', 'الظهران', 'الجبيل'],
            ],
        ];

        $createdCount = 0;
        $failedCount = 0;

        foreach ($privateDrivers as $driverData) {
            try {
                // إنشاء المستخدم
                $user = User::create([
                    'name' => $driverData['user']['name'],
                    'national_id' => $driverData['user']['national_id'],
                    'gender' => $driverData['user']['gender'],
                    'phone' => $driverData['user']['phone'],
                    'password' => Hash::make($driverData['user']['password']),
                    'mobile_verified_at' => $driverData['user']['mobile_verified_at'],
                    'user_type' => $driverData['user']['user_type'],
                    'is_active' => true,
                    'status' => 'active',
                ]);

                // إنشاء معلومات السائق
                $driver = Driver::create([
                    'user_id' => $user->id,
                    'bio' => $driverData['driver']['bio'],
                    'availability_status' => $driverData['driver']['availability_status'],
                    'avg_rating' => $driverData['driver']['avg_rating'],
                ]);

                // البحث عن Brand
                $brand = Brand::where('name', $driverData['vehicle']['brand_name'])->first();

                if (!$brand) {
                    $this->command->warn("⚠️ Brand '{$driverData['vehicle']['brand_name']}' غير موجود");
                    $failedCount++;
                    continue;
                }

                // البحث عن Model
                $vehicleModel = VehicleModel::where('brand_id', $brand->id)
                    ->where('is_active', true)
                    ->first();

                if (!$vehicleModel) {
                    $this->command->warn("⚠️ لا يوجد موديل نشط لـ {$brand->name}");
                    $failedCount++;
                    continue;
                }

                // إنشاء السيارة
                $vehicle = Vehicle::create([
                    'driver_id' => $user->id, // driver_id يشير إلى جدول users وليس drivers
                    'brand_id' => $brand->id,
                    'vehicle_model_id' => $vehicleModel->id,
                    'plate_number' => $driverData['vehicle']['plate_number'],
                    'seat_count' => $driverData['vehicle']['seat_count'],
                    'type' => $driverData['vehicle']['type'],
                    'is_active' => $driverData['vehicle']['is_active'],
                ]);

                // إضافة Amenities
                if (!empty($driverData['amenities'])) {
                    foreach ($driverData['amenities'] as $amenityName => $price) {
                        $amenity = Amenity::where('name', $amenityName)->first();

                        if ($amenity) {
                            VehicleAmenity::create([
                                'vehicle_id' => $vehicle->id,
                                'amenity_id' => $amenity->id,
                                'price' => $price,
                            ]);
                        }
                    }
                }

                // ربط السائق بالمدن
                if (!empty($driverData['cities'])) {
                    foreach ($driverData['cities'] as $cityName) {
                        $city = City::where('name->ar', $cityName)->first();
                        if ($city) {
                            $user->cities()->attach($city->id);
                        }
                    }
                }

                $createdCount++;
                $this->command->info("✅ تم إنشاء السائق الخاص: {$user->name}");

            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("❌ فشل إنشاء السائق: {$driverData['user']['name']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} سائق خاص بنجاح!");

        if ($failedCount > 0) {
            $this->command->warn("⚠️ فشل إنشاء {$failedCount} سائق");
        }
    }
}
