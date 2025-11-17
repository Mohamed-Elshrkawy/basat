<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleAmenity;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriversSeeder extends Seeder
{
    public function run(): void
    {
        // التأكد من وجود Brands و Models و Amenities
        $brands = Brand::where('is_active', true)->get();
        $amenities = Amenity::all();

        if ($brands->isEmpty()) {
            $this->command->warn('⚠️ لا توجد علامات تجارية (Brands) نشطة في قاعدة البيانات!');
            $this->command->info('💡 قم بتشغيل BrandsSeeder أولاً');
            return;
        }

        $drivers = [
            // سائقون من الرياض
            [
                'user' => [
                    'name' => 'أحمد محمد العتيبي',
                    'national_id' => '1012345678',
                    'gender' => 'male',
                    'phone' => '0501234567',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق محترف مع خبرة 10 سنوات في نقل الركاب بين المدن',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'مرسيدس',
                    'plate_number' => 'أ ب ج 1234',
                    'seat_count' => 50,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 20.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'محمد عبدالله القحطاني',
                    'national_id' => '1023456789',
                    'gender' => 'male',
                    'phone' => '0502345678',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق متميز ملتزم بمواعيد الرحلات',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'فولفو',
                    'plate_number' => 'ب ج د 2345',
                    'seat_count' => 45,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 15.00,
                    'تكييف' => 0.00,
                    'USB للشحن' => 5.00,
                ]
            ],

            // سائقون من جدة
            [
                'user' => [
                    'name' => 'خالد سعد الغامدي',
                    'national_id' => '1034567890',
                    'gender' => 'male',
                    'phone' => '0503456789',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'خبرة واسعة في الرحلات الطويلة والقصيرة',
                    'availability_status' => 'available',
                    'avg_rating' => 4.7,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'ج د ه 3456',
                    'seat_count' => 40,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 10.00,
                    'تكييف' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'عبدالرحمن فهد الشهري',
                    'national_id' => '1045678901',
                    'gender' => 'male',
                    'phone' => '0504567890',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق حاصل على جوائز تقدير من الركاب',
                    'availability_status' => 'available',
                    'avg_rating' => 5.0,
                ],
                'vehicle' => [
                    'brand_name' => 'مرسيدس',
                    'plate_number' => 'د ه و 4567',
                    'seat_count' => 52,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 25.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'شاشات ترفيه' => 15.00,
                ]
            ],

            // سائقون من مكة
            [
                'user' => [
                    'name' => 'سعد إبراهيم الزهراني',
                    'national_id' => '1056789012',
                    'gender' => 'male',
                    'phone' => '0505678901',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في رحلات الحج والعمرة',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'يوتونغ',
                    'plate_number' => 'ه و ز 5678',
                    'seat_count' => 48,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],

            // سائقون من المدينة
            [
                'user' => [
                    'name' => 'يوسف عمر الحربي',
                    'national_id' => '1067890123',
                    'gender' => 'male',
                    'phone' => '0506789012',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق محترف مع سجل نظيف',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'فولفو',
                    'plate_number' => 'و ز ح 6789',
                    'seat_count' => 50,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 20.00,
                    'تكييف' => 0.00,
                ]
            ],

            // سائقون من الدمام
            [
                'user' => [
                    'name' => 'عبدالعزيز ناصر العجمي',
                    'national_id' => '1078901234',
                    'gender' => 'male',
                    'phone' => '0507890123',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'خبرة 15 سنة في النقل الجماعي',
                    'availability_status' => 'available',
                    'avg_rating' => 4.7,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'ز ح ط 7890',
                    'seat_count' => 45,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 15.00,
                    'تكييف' => 0.00,
                    'USB للشحن' => 5.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'فهد سلطان السبيعي',
                    'national_id' => '1089012345',
                    'gender' => 'male',
                    'phone' => '0508901234',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق موثوق ومحبوب من قبل الركاب',
                    'availability_status' => 'on_trip',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'مرسيدس',
                    'plate_number' => 'ح ط ي 8901',
                    'seat_count' => 40,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],

            // سائقون من أبها
            [
                'user' => [
                    'name' => 'علي حسن القرني',
                    'national_id' => '1090123456',
                    'gender' => 'male',
                    'phone' => '0509012345',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في رحلات المناطق الجبلية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'يوتونغ',
                    'plate_number' => 'ط ي ك 9012',
                    'seat_count' => 45,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 10.00,
                    'تكييف' => 0.00,
                ]
            ],

            // سائقون من الطائف
            [
                'user' => [
                    'name' => 'بندر مشعل الثبيتي',
                    'national_id' => '1101234567',
                    'gender' => 'male',
                    'phone' => '0510123456',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق ماهر في الطرق الجبلية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.6,
                ],
                'vehicle' => [
                    'brand_name' => 'فورد',
                    'plate_number' => 'ي ك ل 0123',
                    'seat_count' => 48,
                    'type' => 'public_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'WiFi' => 20.00,
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],

            // سائق غير نشط (للاختبار)
            [
                'user' => [
                    'name' => 'ماجد راشد المطيري',
                    'national_id' => '1112345678',
                    'gender' => 'male',
                    'phone' => '0511234567',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active'=>1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق في إجازة',
                    'availability_status' => 'unavailable',
                    'avg_rating' => 4.5,
                ],
                'vehicle' => [
                    'brand_name' => 'فولفو',
                    'plate_number' => 'ك ل م 1234',
                    'seat_count' => 40,
                    'type' => 'public_bus',
                    'is_active' => false,
                ],
                'amenities' => []
            ],
        ];

        $createdCount = 0;
        $failedCount = 0;

        foreach ($drivers as $driverData) {
            try {
                // إنشاء المستخدم
                $user = User::create([
                    'name' => $driverData['user']['name'],
                    'national_id' => $driverData['user']['national_id'],
                    'gender' => $driverData['user']['gender'],
                    'phone' => $driverData['user']['phone'],
                    'password' => Hash::make($driverData['user']['password']),
                    'is_active'=>true,
                    'mobile_verified_at' => $driverData['user']['mobile_verified_at'],
                    'user_type' => $driverData['user']['user_type'],
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
                    continue;
                }

                // البحث عن Model
                $vehicleModel = VehicleModel::where('brand_id', $brand->id)
                    ->where('is_active', true)
                    ->first();

                if (!$vehicleModel) {
                    $this->command->warn("⚠️ لا يوجد موديل نشط لـ {$brand->name}");
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

                $createdCount++;
                $this->command->info("✅ تم إنشاء السائق: {$user->name}");

            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("❌ فشل إنشاء السائق: {$driverData['user']['name']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} سائق بنجاح!");

        if ($failedCount > 0) {
            $this->command->warn("⚠️ فشل إنشاء {$failedCount} سائق");
        }
    }
}
