<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Enums\Settings;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 🎯 القيم الافتراضية لكل إعداد
        $defaults = [
            #--------------------------------------------
            # CONTACT INFORMATION
            #--------------------------------------------
            Settings::CONTACT_EMAIL->value => 'info@transport.com',
            Settings::CONTACT_PHONE->value => '+966500000000',
            Settings::CONTACT_WHATSAPP->value => '+966500000000',
            Settings::CONTACT_ADDRESS->value => 'الرياض، المملكة العربية السعودية',
            Settings::FACEBOOK_URL->value => '',
            Settings::TWITTER_URL->value => '',
            Settings::INSTAGRAM_URL->value => '',
            Settings::YOUTUBE_URL->value => '',
            Settings::SNAPCHAT_URL->value => '',
            Settings::TIKTOK_URL->value => '',

            #--------------------------------------------
            # PLATFORM SETTINGS
            #--------------------------------------------
            Settings::ENABLE_SEAT_BOOKING->value => '1',   // تفعيل حجز المقعد
            Settings::ENABLE_PRIVATE_BUS->value => '1',     // تفعيل الباص الخاص
            Settings::ENABLE_SUBSCRIPTIONS->value => '1',   // تفعيل الاشتراكات

            Settings::TAX_PERCENTAGE_PUBLIC->value => '15',     // ضريبة النقل العام
            Settings::TAX_PERCENTAGE_PRIVATE->value => '10',    // ضريبة النقل الخاص
            Settings::TAX_PERCENTAGE_SCHOOL->value => '0',      // ضريبة المدارس

            Settings::APP_FEE_PERCENTAGE_PUBLIC->value => '5',  // عمولة المنصة للنقل العام
            Settings::APP_FEE_PERCENTAGE_PRIVATE->value => '7', // عمولة المنصة للنقل الخاص
            Settings::APP_FEE_PERCENTAGE_SCHOOL->value => '0',  // عمولة المدارس

            #--------------------------------------------
            # APP INFO
            #--------------------------------------------
            Settings::APP_NAME_AR->value => 'منصة النقل',
            Settings::APP_NAME_EN->value => 'Transport Platform',
            Settings::APP_VERSION->value => '1.0.0',
            Settings::APP_LOGO->value => '',
            Settings::APP_ICON->value => '',

            #--------------------------------------------
            # PAYMENT METHODS
            #--------------------------------------------
            Settings::PAYMENT_CREDIT_CARD->value => '1',
            Settings::PAYMENT_MADA->value => '1',
            Settings::PAYMENT_APPLE_PAY->value => '1',
            Settings::PAYMENT_STC_PAY->value => '1',
            Settings::PAYMENT_CASH->value => '1',
        ];


        foreach (Settings::cases() as $case) {
            $meta = $case->metadata();

            Setting::updateOrCreate(
                ['key' => $case->value],
                [
                    'key'   => $case->value,
                    'value' => $defaults[$case->value] ?? '',
                    'group' => $meta['group'] ?? 'general',
                    'type'  => $meta['type'] ?? 'text',
                ]
            );
        }

        $this->command->info('✅ Settings seeded successfully!');
    }
}
