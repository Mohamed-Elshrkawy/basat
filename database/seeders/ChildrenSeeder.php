<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use App\Models\Child;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ChildrenSeeder extends Seeder
{
    public function run(): void
    {
        $parents = User::where('user_type', UserTypeEnum::Customer->value)->get();

        if ($parents->isEmpty()) {
            $this->command->warn('⚠️ لا يوجد آباء في قاعدة البيانات!');
            return;
        }

        // أسماء عربية للأطفال
        $arabicBoyNames = [
            'محمد', 'أحمد', 'خالد', 'عبدالله', 'سعد', 'فهد', 'عبدالرحمن', 'سلطان',
            'يوسف', 'عمر', 'علي', 'حسن', 'حسين', 'ماجد', 'نواف', 'تركي'
        ];

        $arabicGirlNames = [
            'فاطمة', 'عائشة', 'مريم', 'نورة', 'سارة', 'ريم', 'لمى', 'رهف',
            'جود', 'لين', 'غلا', 'شهد', 'جنى', 'رغد', 'أسماء', 'هند'
        ];

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("👶 جاري إنشاء الأطفال للعملاء...");
        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $createdCount = 0;

        foreach ($parents as $parent) {
            // عدد عشوائي من الأطفال لكل عميل (1-4)
            $childrenCount = rand(1, 4);

            for ($i = 0; $i < $childrenCount; $i++) {
                $gender = rand(0, 1) === 0 ? 'male' : 'female';
                $name = $gender === 'male'
                    ? $arabicBoyNames[array_rand($arabicBoyNames)]
                    : $arabicGirlNames[array_rand($arabicGirlNames)];

                // عمر عشوائي بين 5-17 سنة
                $age = rand(5, 17);
                $birthDate = Carbon::now()->subYears($age)->subDays(rand(1, 365));

                $child = Child::create([
                    'parent_id' => $parent->id,
                    'name' => $name . ' ' . $parent->name,
                    'phone' => null, // اختياري
                    'gender' => $gender,
                    'birth_date' => $birthDate->format('Y-m-d'),
                ]);

                $createdCount++;
            }

            $this->command->line("  ✅ {$parent->name} - {$childrenCount} " . ($childrenCount === 1 ? 'طفل' : 'أطفال'));
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} طفل بنجاح!");
        $this->command->info("📊 إجمالي الأطفال في النظام: " . Child::count());
        $this->command->info("👨‍👩‍👧‍👦 عدد الآباء الذين لديهم أطفال: " . $parents->count());
    }
}
