<?php

namespace Database\Seeders;

use App\Models\Rating;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Database\Seeder;

class RatingsSeeder extends Seeder
{
    public function run(): void
    {
        // الحصول على الحجوزات المكتملة
        $completedBookings = Booking::where('status', 'completed')->get();

        if ($completedBookings->isEmpty()) {
            $this->command->info('ℹ️ لا توجد حجوزات مكتملة، سيتم تقييم السائقين بشكل عشوائي');

            // تقييم السائقين بشكل عشوائي
            $drivers = User::where('user_type', 'driver')->get();
            $passengers = User::where('user_type', 'passenger')
                ->where('mobile_verified_at', '!=', null)
                ->get();

            if ($drivers->isEmpty() || $passengers->isEmpty()) {
                $this->command->warn('⚠️ لا يوجد سائقين أو ركاب في قاعدة البيانات!');
                return;
            }

            $comments = [
                'سائق ممتاز وملتزم بالمواعيد',
                'رحلة مريحة وسائق محترف',
                'خدمة ممتازة وأسلوب راقي',
                'تجربة رائعة، أنصح به',
                'سائق محترم ومتعاون',
                'رحلة آمنة ومريحة',
                'خدمة جيدة بشكل عام',
                'سائق ماهر وطريق سليم',
                'تعامل ممتاز وسيارة نظيفة',
                'رحلة ممتعة، شكراً',
            ];

            $createdCount = 0;

            foreach ($drivers->take(10) as $driver) {
                try {
                    $rating = rand(3, 5);

                    Rating::create([
                        'rater_id' => $passengers->random()->id,
                        'rated_id' => $driver->id,
                        'rating' => $rating,
                        'comment' => $comments[array_rand($comments)],
                        'type' => 'driver',
                    ]);

                    $createdCount++;
                } catch (\Exception $e) {
                    // تجاهل الأخطاء
                }
            }

            $this->command->info("✅ تم إنشاء {$createdCount} تقييم بنجاح!");
            return;
        }

        $comments = [
            'رحلة ممتازة، سائق محترف جداً',
            'تجربة رائعة، الباص نظيف ومريح',
            'سائق ملتزم بالمواعيد ومحترم',
            'خدمة ممتازة، أنصح بالحجز معهم',
            'رحلة مريحة وآمنة',
            'سائق ماهر وطريقة قيادة ممتازة',
            'خدمة جيدة جداً',
            'تعامل راقي وخدمة متميزة',
            'رحلة ممتعة، شكراً للسائق',
            'سيارة نظيفة وسائق محترم',
        ];

        $createdCount = 0;

        foreach ($completedBookings->take(10) as $booking) {
            try {
                $rating = rand(3, 5);

                // تقييم السائق
                if ($booking->type === 'public_bus' && $booking->schedule && $booking->schedule->driver_id) {
                    Rating::create([
                        'rater_id' => $booking->user_id,
                        'rated_id' => $booking->schedule->driver_id,
                        'rating' => $rating,
                        'comment' => $comments[array_rand($comments)],
                        'type' => 'driver',
                    ]);
                    $createdCount++;
                } elseif ($booking->type === 'private_bus' && $booking->driver_id) {
                    Rating::create([
                        'rater_id' => $booking->user_id,
                        'rated_id' => $booking->driver_id,
                        'rating' => $rating,
                        'comment' => $comments[array_rand($comments)],
                        'type' => 'driver',
                    ]);
                    $createdCount++;
                }

            } catch (\Exception $e) {
                // تجاهل الأخطاء (مثل التقييم المكرر)
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} تقييم بنجاح!");
    }
}
