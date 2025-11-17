<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\City;
use Illuminate\Database\Seeder;

class SchoolsSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all();

        if ($cities->isEmpty()) {
            $this->command->warn('⚠️ لا توجد مدن في قاعدة البيانات!');
            return;
        }

        $schools = [
            [
                'name' => [
                    'ar' => 'مدرسة النور الأهلية',
                    'en' => 'Al Noor Private School'
                ],
                'city' => 'الرياض',
                'address' => 'حي العليا، شارع الملك فهد',
                'phone' => '0112345678',
                'email' => 'info@alnoor-school.edu.sa',
                'principal_name' => 'أحمد بن سعيد المحمود',
                'principal_phone' => '0501111111',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'school_start_time' => '07:00',
                'school_end_time' => '13:30',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة العلم والمعرفة',
                    'en' => 'Knowledge School'
                ],
                'city' => 'الرياض',
                'address' => 'حي الملز، طريق الملك عبدالله',
                'phone' => '0112345679',
                'email' => 'info@knowledge-school.edu.sa',
                'principal_name' => 'فاطمة بنت محمد الدوسري',
                'principal_phone' => '0502222222',
                'latitude' => 24.6877,
                'longitude' => 46.7219,
                'school_start_time' => '07:15',
                'school_end_time' => '14:00',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الرواد العالمية',
                    'en' => 'Al Ruwad International School'
                ],
                'city' => 'جدة',
                'address' => 'حي الحمراء، شارع فلسطين',
                'phone' => '0122345678',
                'email' => 'info@ruwad-school.edu.sa',
                'principal_name' => 'خالد بن عبدالرحمن الغامدي',
                'principal_phone' => '0503333333',
                'latitude' => 21.5811,
                'longitude' => 39.1570,
                'school_start_time' => '07:00',
                'school_end_time' => '13:45',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة المستقبل الواعد',
                    'en' => 'Promising Future School'
                ],
                'city' => 'جدة',
                'address' => 'حي البلد، الكورنيش الشمالي',
                'phone' => '0122345679',
                'email' => 'info@future-school.edu.sa',
                'principal_name' => 'سارة بنت علي الزهراني',
                'principal_phone' => '0504444444',
                'latitude' => 21.4858,
                'longitude' => 39.1925,
                'school_start_time' => '07:30',
                'school_end_time' => '14:00',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة التميز الأهلية',
                    'en' => 'Excellence Private School'
                ],
                'city' => 'الدمام',
                'address' => 'حي الفيصلية، شارع الظهران',
                'phone' => '0132345678',
                'email' => 'info@excellence-school.edu.sa',
                'principal_name' => 'عبدالله بن فهد العجمي',
                'principal_phone' => '0505555555',
                'latitude' => 26.4207,
                'longitude' => 50.0888,
                'school_start_time' => '07:00',
                'school_end_time' => '13:30',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الإبداع النموذجية',
                    'en' => 'Creativity Model School'
                ],
                'city' => 'الخبر',
                'address' => 'حي الراكة، شارع الأمير محمد',
                'phone' => '0132345679',
                'email' => 'info@creativity-school.edu.sa',
                'principal_name' => 'نورة بنت سعد القحطاني',
                'principal_phone' => '0506666666',
                'latitude' => 26.2885,
                'longitude' => 50.2080,
                'school_start_time' => '07:15',
                'school_end_time' => '13:45',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الأمل الدولية',
                    'en' => 'Hope International School'
                ],
                'city' => 'مكة المكرمة',
                'address' => 'حي العزيزية، شارع مكة جدة السريع',
                'phone' => '0122456789',
                'email' => 'info@hope-school.edu.sa',
                'principal_name' => 'محمد بن إبراهيم الشريف',
                'principal_phone' => '0507777777',
                'latitude' => 21.4247,
                'longitude' => 39.8175,
                'school_start_time' => '07:00',
                'school_end_time' => '13:30',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الفجر الجديد',
                    'en' => 'New Dawn School'
                ],
                'city' => 'المدينة المنورة',
                'address' => 'حي قباء، طريق المدينة المنورة',
                'phone' => '0142345678',
                'email' => 'info@newdawn-school.edu.sa',
                'principal_name' => 'عمر بن حسن الحربي',
                'principal_phone' => '0508888888',
                'latitude' => 24.4418,
                'longitude' => 39.6170,
                'school_start_time' => '07:30',
                'school_end_time' => '14:00',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الأجيال الذكية',
                    'en' => 'Smart Generations School'
                ],
                'city' => 'أبها',
                'address' => 'حي الجبل الأخضر، طريق الملك فهد',
                'phone' => '0172345678',
                'email' => 'info@smartgen-school.edu.sa',
                'principal_name' => 'ريم بنت خالد القرني',
                'principal_phone' => '0509999999',
                'latitude' => 18.2164,
                'longitude' => 42.5053,
                'school_start_time' => '07:00',
                'school_end_time' => '13:45',
                'is_active' => true,
            ],
            [
                'name' => [
                    'ar' => 'مدرسة الطائف النموذجية',
                    'en' => 'Taif Model School'
                ],
                'city' => 'الطائف',
                'address' => 'حي الحوية، شارع الستين',
                'phone' => '0122567890',
                'email' => 'info@taif-model.edu.sa',
                'principal_name' => 'بندر بن مشعل الثبيتي',
                'principal_phone' => '0500000001',
                'latitude' => 21.2703,
                'longitude' => 40.4158,
                'school_start_time' => '07:15',
                'school_end_time' => '13:30',
                'is_active' => true,
            ],
            // مدرسة غير نشطة (للاختبار)
            [
                'name' => [
                    'ar' => 'مدرسة الأفق - مغلقة مؤقتاً',
                    'en' => 'Horizon School - Temporarily Closed'
                ],
                'city' => 'الرياض',
                'address' => 'حي النخيل',
                'phone' => '0112999999',
                'email' => 'info@horizon-school.edu.sa',
                'principal_name' => 'غير محدد',
                'principal_phone' => '0500000000',
                'latitude' => 24.7500,
                'longitude' => 46.6500,
                'school_start_time' => '07:00',
                'school_end_time' => '13:30',
                'is_active' => false,
            ],
        ];

        $createdCount = 0;

        foreach ($schools as $schoolData) {
            try {
                $city = City::where('name->ar', $schoolData['city'])->first();

                if (!$city) {
                    $this->command->warn("⚠️ المدينة '{$schoolData['city']}' غير موجودة");
                    continue;
                }

                School::create([
                    'name' => $schoolData['name'],
                    'lat' => $schoolData['latitude'],
                    'lng' => $schoolData['longitude'],
                    'is_active' => $schoolData['is_active'],
                ]);

                $createdCount++;
                $this->command->info("✅ تم إنشاء المدرسة: {$schoolData['name']['ar']}");

            } catch (\Exception $e) {
                $this->command->error("❌ فشل إنشاء المدرسة: {$schoolData['name']['ar']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} مدرسة بنجاح!");
    }
}
