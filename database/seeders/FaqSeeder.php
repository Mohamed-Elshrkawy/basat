<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        Faq::create([
            'question' => ['en' => 'Where can I download the app?', 'ar' => 'من أين يمكنني تحميل التطبيق؟'],
            'answer' => ['en' => 'You can download the app from the Google Play Store for Android devices and the App Store for iOS devices.', 'ar' => 'يمكنك تحميل التطبيق من متجر Google Play لأجهزة Android ومتجر App Store لأجهزة iOS.'],
            'order_column' => 1,
        ]);

        Faq::create([
            'question' => ['en' => 'Can I cancel my booking?', 'ar' => 'هل يمكنني إلغاء الحجز؟'],
            'answer' => ['en' => 'Yes, you can cancel the booking at least one hour before the trip time to get a full refund in your wallet.', 'ar' => 'نعم، يمكنك إلغاء الحجز قبل موعد الرحلة بساعة واحدة على الأقل لاسترداد المبلغ كاملاً في المحفظة.'],
            'order_column' => 2,
        ]);
    }
} 