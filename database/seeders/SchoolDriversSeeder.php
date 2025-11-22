<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleAmenity;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\Amenity;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolDriversSeeder extends Seeder
{
    public function run(): void
    {
        // التأكد من وجود Brands و Models و Amenities
        $brands = Brand::where('is_active', true)->get();
        $amenities = Amenity::all();
        $schools = School::where('is_active', true)->get();

        if ($brands->isEmpty()) {
            $this->command->warn('⚠️ لا توجد علامات تجارية (Brands) نشطة في قاعدة البيانات!');
            $this->command->info('💡 قم بتشغيل BrandsSeeder أولاً');
            return;
        }

        $drivers = [
            [
                'user' => [
                    'name' => 'سعيد محمد العمري',
                    'national_id' => '1201234567',
                    'gender' => 'male',
                    'phone' => '0560111111',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق باص مدرسي محترف مع خبرة 8 سنوات في نقل الطلاب',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'م د س 1111',
                    'seat_count' => 30,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'عبدالله فيصل الدوسري',
                    'national_id' => '1202345678',
                    'gender' => 'male',
                    'phone' => '0560222222',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق ملتزم بسلامة الطلاب والمواعيد المدرسية',
                    'availability_status' => 'available',
                    'avg_rating' => 5.0,
                ],
                'vehicle' => [
                    'brand_name' => 'هيونداي',
                    'plate_number' => 'م د س 2222',
                    'seat_count' => 25,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'GPS' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'أحمد سالم القحطاني',
                    'national_id' => '1203456789',
                    'gender' => 'male',
                    'phone' => '0560333333',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'خبرة واسعة في نقل طلاب المدارس الابتدائية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'فورد',
                    'plate_number' => 'م د س 3333',
                    'seat_count' => 35,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'خالد راشد الحربي',
                    'national_id' => '1204567890',
                    'gender' => 'male',
                    'phone' => '0560444444',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق حاصل على شهادات السلامة المرورية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'م د س 4444',
                    'seat_count' => 28,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'GPS' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'ماجد عبدالعزيز السبيعي',
                    'national_id' => '1205678901',
                    'gender' => 'male',
                    'phone' => '0560555555',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في نقل طلاب المدارس الدولية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.7,
                ],
                'vehicle' => [
                    'brand_name' => 'هيونداي',
                    'plate_number' => 'م د س 5555',
                    'seat_count' => 32,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'GPS' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'نواف حسن الزهراني',
                    'national_id' => '1206789012',
                    'gender' => 'male',
                    'phone' => '0560666666',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق محبوب من قبل الطلاب وأولياء الأمور',
                    'availability_status' => 'available',
                    'avg_rating' => 5.0,
                ],
                'vehicle' => [
                    'brand_name' => 'فورد',
                    'plate_number' => 'م د س 6666',
                    'seat_count' => 30,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'طارق سعد العتيبي',
                    'national_id' => '1207890123',
                    'gender' => 'male',
                    'phone' => '0560777777',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'خبرة 10 سنوات في النقل المدرسي',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'م د س 7777',
                    'seat_count' => 26,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'بدر يوسف الغامدي',
                    'national_id' => '1208901234',
                    'gender' => 'male',
                    'phone' => '0560888888',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'سائق موثوق ومتميز في التعامل مع الطلاب',
                    'availability_status' => 'available',
                    'avg_rating' => 4.9,
                ],
                'vehicle' => [
                    'brand_name' => 'هيونداي',
                    'plate_number' => 'م د س 8888',
                    'seat_count' => 29,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'GPS' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'عمر إبراهيم الشهري',
                    'national_id' => '1209012345',
                    'gender' => 'male',
                    'phone' => '0560999999',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'حاصل على جوائز التميز في النقل المدرسي',
                    'availability_status' => 'available',
                    'avg_rating' => 5.0,
                ],
                'vehicle' => [
                    'brand_name' => 'فورد',
                    'plate_number' => 'م د س 9999',
                    'seat_count' => 33,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                    'مقاعد مريحة' => 0.00,
                    'GPS' => 0.00,
                ]
            ],
            [
                'user' => [
                    'name' => 'سلطان ناصر القرني',
                    'national_id' => '1210123456',
                    'gender' => 'male',
                    'phone' => '0560000000',
                    'password' => 'password123',
                    'mobile_verified_at' => now(),
                    'is_active' => 1,
                    'user_type' => 'driver',
                ],
                'driver' => [
                    'bio' => 'متخصص في المدارس الأهلية والعالمية',
                    'availability_status' => 'available',
                    'avg_rating' => 4.8,
                ],
                'vehicle' => [
                    'brand_name' => 'تويوتا',
                    'plate_number' => 'م د س 0000',
                    'seat_count' => 31,
                    'type' => 'school_bus',
                    'is_active' => true,
                ],
                'amenities' => [
                    'تكييف' => 0.00,
                ]
            ],
        ];

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("🚌 جاري إنشاء سائقي الباص المدرسي...");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

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
                    'is_active' => true,
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

                // إنشاء السيارة (باص مدرسي)
                $vehicle = Vehicle::create([
                    'driver_id' => $user->id,
                    'brand_id' => $brand->id,
                    'vehicle_model_id' => $vehicleModel->id,
                    'plate_number' => $driverData['vehicle']['plate_number'],
                    'seat_count' => $driverData['vehicle']['seat_count'],
                    'type' => 'school_bus',
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

                // ربط السائق بمدرسة أو مدرستين عشوائياً
                if ($schools->isNotEmpty()) {
                    $randomSchools = $schools->random(rand(1, min(2, $schools->count())));
                    $user->schools()->syncWithoutDetaching($randomSchools->pluck('id'));
                }

                $createdCount++;
                $schoolCount = $user->schools()->count();
                $this->command->line("  ✅ {$user->name} - {$schoolCount} " . ($schoolCount === 1 ? 'مدرسة' : 'مدارس'));

            } catch (\Exception $e) {
                $failedCount++;
                $this->command->error("❌ فشل إنشاء السائق: {$driverData['user']['name']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} سائق باص مدرسي بنجاح!");

        if ($failedCount > 0) {
            $this->command->warn("⚠️ فشل إنشاء {$failedCount} سائق");
        }
    }
}
