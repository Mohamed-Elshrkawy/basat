<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\User;
use App\Models\School;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ChildrenSeeder extends Seeder
{
    public function run(): void
    {
        $parents = User::where('user_type', 'passenger')
            ->where('mobile_verified_at', '!=', null)
            ->whereIn('national_id', ['1301234567', '1312345678', '1323456789'])
            ->get();

        $schools = School::where('is_active', true)->get();

        if ($parents->isEmpty()) {
            $this->command->warn('⚠️ لا يوجد آباء في قاعدة البيانات!');
            return;
        }

        if ($schools->isEmpty()) {
            $this->command->warn('⚠️ لا توجد مدارس في قاعدة البيانات!');
            return;
        }

        $children = [
            [
                'parent_index' => 0,
                'name' => 'عبدالله عبدالعزيز الثبيتي',
                'national_id' => '1401234567',
                'birth_date' => Carbon::now()->subYears(8),
                'gender' => 'male',
                'grade' => 'الصف الثالث الابتدائي',
                'school_index' => 0,
                'is_active' => true,
            ],
            [
                'parent_index' => 0,
                'name' => 'لمى عبدالعزيز الثبيتي',
                'national_id' => '1402234567',
                'birth_date' => Carbon::now()->subYears(6),
                'gender' => 'female',
                'grade' => 'الصف الأول الابتدائي',
                'school_index' => 0,
                'is_active' => true,
            ],
            [
                'parent_index' => 1,
                'name' => 'سارة ناصر السبيعي',
                'national_id' => '1403234567',
                'birth_date' => Carbon::now()->subYears(10),
                'gender' => 'female',
                'grade' => 'الصف الخامس الابتدائي',
                'school_index' => 1,
                'is_active' => true,
            ],
            [
                'parent_index' => 1,
                'name' => 'يوسف ناصر السبيعي',
                'national_id' => '1404234567',
                'birth_date' => Carbon::now()->subYears(7),
                'gender' => 'male',
                'grade' => 'الصف الثاني الابتدائي',
                'school_index' => 1,
                'is_active' => true,
            ],
            [
                'parent_index' => 2,
                'name' => 'ريم فيصل العنزي',
                'national_id' => '1405234567',
                'birth_date' => Carbon::now()->subYears(9),
                'gender' => 'female',
                'grade' => 'الصف الرابع الابتدائي',
                'school_index' => 2,
                'is_active' => true,
            ],
            [
                'parent_index' => 2,
                'name' => 'خالد فيصل العنزي',
                'national_id' => '1406234567',
                'birth_date' => Carbon::now()->subYears(11),
                'gender' => 'male',
                'grade' => 'الصف السادس الابتدائي',
                'school_index' => 2,
                'is_active' => true,
            ],
            [
                'parent_index' => 2,
                'name' => 'نور فيصل العنزي',
                'national_id' => '1407234567',
                'birth_date' => Carbon::now()->subYears(5),
                'gender' => 'female',
                'grade' => 'التمهيدي',
                'school_index' => 2,
                'is_active' => true,
            ],
        ];

        $createdCount = 0;

        foreach ($children as $childData) {
            try {
                if (!isset($parents[$childData['parent_index']])) {
                    continue;
                }

                if (!isset($schools[$childData['school_index']])) {
                    continue;
                }

                Child::create([
                    'parent_id' => $parents[$childData['parent_index']]->id,
                    'name' => $childData['name'],
                    'national_id' => $childData['national_id'],
                    'birth_date' => $childData['birth_date'],
                    'gender' => $childData['gender'],
                    'grade' => $childData['grade'],
                    'school_id' => $schools[$childData['school_index']]->id,
                    'is_active' => $childData['is_active'],
                ]);

                $createdCount++;
                $this->command->info("✅ تم إنشاء الطفل: {$childData['name']}");

            } catch (\Exception $e) {
                $this->command->error("❌ فشل إنشاء الطفل: {$childData['name']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} طفل بنجاح!");
    }
}
