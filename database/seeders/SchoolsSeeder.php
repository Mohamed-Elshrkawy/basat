<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolPackage;
use App\Models\User;
use Illuminate\Database\Seeder;

class SchoolsSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على الباقات المتاحة
        $packages = SchoolPackage::where('is_active', true)->get();

        if ($packages->isEmpty()) {
            $this->command->warn('⚠️ لا توجد باقات في قاعدة البيانات! قم بتشغيل SchoolPackagesSeeder أولاً.');
            return;
        }

        // الحصول على السائقين الذين لديهم باص مدرسي
        $drivers = User::where('user_type', 'driver')
            ->where('is_active', true)
            ->whereHas('vehicle', function ($query) {
                $query->where('type', 'school_bus');
            })
            ->get();

        $schools = [
            [
                'name' => [
                    'ar' => 'مدرسة الأمل الابتدائية',
                    'en' => 'Al Amal Elementary School',
                ],
                'lat' => 24.7136,
                'lng' => 46.6753,
                'departure_time' => '06:30',
                'return_time' => '13:30',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة النجاح المتوسطة',
                    'en' => 'Al Najah Middle School',
                ],
                'lat' => 24.7245,
                'lng' => 46.6854,
                'departure_time' => '06:45',
                'return_time' => '14:00',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة المستقبل الثانوية',
                    'en' => 'Al Mustaqbal High School',
                ],
                'lat' => 24.7356,
                'lng' => 46.6955,
                'departure_time' => '07:00',
                'return_time' => '14:30',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الفجر الابتدائية',
                    'en' => 'Al Fajr Elementary School',
                ],
                'lat' => 24.7020,
                'lng' => 46.6640,
                'departure_time' => '06:30',
                'return_time' => '13:00',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الرياض العالمية',
                    'en' => 'Riyadh International School',
                ],
                'lat' => 24.7467,
                'lng' => 46.7056,
                'departure_time' => '07:15',
                'return_time' => '15:00',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة العلم والمعرفة',
                    'en' => 'Knowledge School',
                ],
                'lat' => 24.6890,
                'lng' => 46.6530,
                'departure_time' => '06:45',
                'return_time' => '13:45',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة النور الأهلية',
                    'en' => 'Al Noor Private School',
                ],
                'lat' => 24.7578,
                'lng' => 46.7157,
                'departure_time' => '07:00',
                'return_time' => '14:15',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة التميز الابتدائية',
                    'en' => 'Excellence Elementary School',
                ],
                'lat' => 24.6780,
                'lng' => 46.6420,
                'departure_time' => '06:30',
                'return_time' => '13:30',
                'working_days' => ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday'],
                'is_active' => true,
            ],
        ];

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("🏫 جاري إنشاء المدارس...");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $createdCount = 0;

        foreach ($schools as $schoolData) {
            $school = School::firstOrCreate(
                ['name' => $schoolData['name']],
                $schoolData
            );

            if ($school->wasRecentlyCreated) {
                $createdCount++;

                // ربط 3-5 باقات عشوائية بالمدرسة
                $randomPackages = $packages->random(rand(3, min(5, $packages->count())));
                $school->packages()->syncWithoutDetaching($randomPackages->pluck('id'));

                // ربط 1-3 سائقين عشوائيين بالمدرسة (إن وجدوا)
                if ($drivers->isNotEmpty()) {
                    $randomDrivers = $drivers->random(rand(1, min(3, $drivers->count())));
                    $school->drivers()->syncWithoutDetaching($randomDrivers->pluck('id'));
                }

                $packageCount = $school->packages()->count();
                $driverCount = $school->drivers()->count();

                $this->command->line("  ✅ {$schoolData['name']['ar']} - {$packageCount} باقات، {$driverCount} سائقين");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} مدرسة بنجاح!");
        $this->command->info("📊 إجمالي المدارس في النظام: " . School::count());
    }
}
