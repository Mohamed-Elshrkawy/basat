<?php

namespace Database\Seeders;

use App\Models\SchoolPackage;
use Illuminate\Database\Seeder;

class SchoolPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => [
                    'ar' => 'باقة شهرية',
                    'en' => 'Monthly Package',
                ],
                'description' => [
                    'ar' => 'باقة شهرية لنقل الطلاب من وإلى المدرسة (ذهاب وعودة)',
                    'en' => 'Monthly package for student transportation to and from school (round trip)',
                ],
                'price' => 300.00,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة فصلية (3 أشهر)',
                    'en' => 'Quarterly Package (3 Months)',
                ],
                'description' => [
                    'ar' => 'باقة 3 أشهر لنقل الطلاب من وإلى المدرسة مع خصم 10%',
                    'en' => '3-month package for student transportation with 10% discount',
                ],
                'price' => 810.00,
                'duration_days' => 90,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة نصف سنوية',
                    'en' => 'Semi-Annual Package',
                ],
                'description' => [
                    'ar' => 'باقة 6 أشهر لنقل الطلاب من وإلى المدرسة مع خصم 15%',
                    'en' => '6-month package for student transportation with 15% discount',
                ],
                'price' => 1530.00,
                'duration_days' => 180,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة سنوية كاملة',
                    'en' => 'Full Year Package',
                ],
                'description' => [
                    'ar' => 'باقة سنة كاملة لنقل الطلاب من وإلى المدرسة مع خصم 20%',
                    'en' => 'Full year package for student transportation with 20% discount',
                ],
                'price' => 2880.00,
                'duration_days' => 360,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة الذهاب فقط (شهري)',
                    'en' => 'One-Way Only (Monthly)',
                ],
                'description' => [
                    'ar' => 'باقة شهرية لنقل الطلاب إلى المدرسة فقط (ذهاب بدون عودة)',
                    'en' => 'Monthly package for one-way transportation to school only',
                ],
                'price' => 180.00,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة العودة فقط (شهري)',
                    'en' => 'Return Only (Monthly)',
                ],
                'description' => [
                    'ar' => 'باقة شهرية لنقل الطلاب من المدرسة فقط (عودة بدون ذهاب)',
                    'en' => 'Monthly package for return transportation from school only',
                ],
                'price' => 180.00,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة VIP شهرية',
                    'en' => 'VIP Monthly Package',
                ],
                'description' => [
                    'ar' => 'باقة شهرية مميزة مع باص حديث ومكيف ومقاعد مريحة',
                    'en' => 'Premium monthly package with modern AC bus and comfortable seats',
                ],
                'price' => 450.00,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'باقة الأنشطة الإضافية',
                    'en' => 'Extra Activities Package',
                ],
                'description' => [
                    'ar' => 'باقة شهرية تشمل نقل الطلاب للأنشطة المدرسية الإضافية',
                    'en' => 'Monthly package including transportation for extra school activities',
                ],
                'price' => 350.00,
                'duration_days' => 30,
                'is_active' => true,
            ],
        ];

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("🎒 جاري إنشاء باقات الباص المدرسي...");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $createdCount = 0;

        foreach ($packages as $packageData) {
            $package = SchoolPackage::firstOrCreate(
                ['name' => $packageData['name']],
                $packageData
            );

            if ($package->wasRecentlyCreated) {
                $createdCount++;
                $this->command->line("  ✅ {$packageData['name']['ar']} - {$packageData['price']} SAR / {$packageData['duration_days']} يوم");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} باقة بنجاح!");
        $this->command->info("📊 إجمالي الباقات في النظام: " . SchoolPackage::count());
    }
}
