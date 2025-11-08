<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaticPage;

class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        StaticPage::create([
            'key' => 'about-us',
            'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
            'content' => ['en' => 'KSA Bus is a leading platform for bus booking services in the Kingdom of Saudi Arabia...', 'ar' => 'حافلات السعودية هي منصة رائدة في خدمات حجز الباصات في المملكة العربية السعودية...'],
        ]);

        StaticPage::create([
            'key' => 'privacy-policy',
            'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
            'content' => ['en' => 'We are committed to protecting your privacy and personal data...', 'ar' => 'نحن نلتزم بحماية خصوصيتك وبياناتك الشخصية...'],
        ]);

        StaticPage::create([
            'key' => 'terms-and-conditions',
            'title' => ['en' => 'Terms and Conditions', 'ar' => 'الشروط والأحكام'],
            'content' => ['en' => 'By using the KSA Bus application, you agree to the following terms and conditions...', 'ar' => 'باستخدامك لتطبيق حافلات السعودية، فإنك توافق على الشروط والأحكام التالية...'],
        ]);

        StaticPage::create([
            'key' => 'cancellation-policy',
            'title' => ['en' => 'Cancellation Policy', 'ar' => 'سياسة الإلغاء'],
            'content' => ['en' => 'You can cancel the booking at least one hour before the trip time to get a full refund in the wallet.', 'ar' => 'يمكنك إلغاء الحجز قبل موعد الرحلة بساعة واحدة على الأقل لاسترداد المبلغ كاملاً في المحفظة.'],
        ]);
        
        StaticPage::create([
            'key' => 'contact-us',
            'title' => ['en' => 'Contact Us', 'ar' => 'تواصل معنا'],
            'content' => ['en' => 'You can contact us via email: support@ksabus.com or phone: +966123456789', 'ar' => 'يمكنك التواصل معنا عبر البريد الإلكتروني: support@ksabus.com أو الهاتف: 966123456789+'],
        ]);
    }
} 