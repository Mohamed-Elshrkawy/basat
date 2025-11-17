<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            [
                'name' => 'محمد بن سعد',
                'email' => 'mohammed.saad@example.com',
                'phone' => '0551234567',
                'type' => 'suggestion',
                'subject' => 'استفسار عن خدمات النقل المدرسي',
                'message' => 'السلام عليكم، أرغب في الاستفسار عن خدمات النقل المدرسي والأسعار المتاحة للعام الدراسي القادم.',
                'status' => 'pending',
            ],
            [
                'name' => 'فاطمة الغامدي',
                'email' => 'fatimah.alghamdi@example.com',
                'phone' => '0552345678',
                'type' => 'complaint',
                'subject' => 'شكوى بخصوص تأخير الحافلة',
                'message' => 'أود الإبلاغ عن تأخر متكرر للحافلة على خط الرياض - جدة في الصباح الباكر.',
                'status' => 'pending',
            ],
            [
                'name' => 'عبدالله القحطاني',
                'email' => 'abdullah.q@example.com',
                'phone' => '0553456789',
                'type' => 'suggestion',
                'subject' => 'طلب تعاون تجاري',
                'message' => 'نحن شركة متخصصة في تنظيم الرحلات السياحية ونود التعاون معكم لتوفير خدمات النقل لعملائنا.',
                'status' => 'pending',
            ],
            [
                'name' => 'سارة المطيري',
                'email' => 'sarah.m@example.com',
                'phone' => '0554567890',
                'type' => 'suggestion',
                'subject' => 'اقتراح لتحسين الخدمة',
                'message' => 'أقترح إضافة خاصية تتبع الحافلة مباشرة على التطبيق لمعرفة الموقع الحالي للحافلة.',
                'status' => 'pending',
            ],
            [
                'name' => 'خالد الشهري',
                'email' => 'khaled.alshehri@example.com',
                'phone' => '0555678901',
                'type' => 'suggestion',
                'subject' => 'استفسار عن الأسعار الخاصة للمجموعات',
                'message' => 'لدينا مجموعة من 50 شخصاً نرغب في السفر من الرياض إلى مكة، هل توجد أسعار خاصة للمجموعات؟',
                'status' => 'pending',
            ],
            [
                'name' => 'نورة الحربي',
                'email' => 'norah.harbi@example.com',
                'phone' => '0556789012',
                'type' => 'complaint',
                'subject' => 'مشكلة في استرداد المبلغ',
                'message' => 'قمت بإلغاء حجزي منذ أسبوع ولم يتم إرجاع المبلغ إلى محفظتي حتى الآن.',
                'status' => 'pending',
            ],
            [
                'name' => 'أحمد العتيبي',
                'email' => 'ahmed.otaibi@example.com',
                'phone' => '0557890123',
                'type' => 'suggestion',
                'subject' => 'شكر وتقدير',
                'message' => 'أود أن أشكركم على الخدمة الممتازة والسائق المحترف في رحلتي الأخيرة من جدة إلى الطائف.',
                'status' => 'pending',
            ],
            [
                'name' => 'ريم الزهراني',
                'email' => 'reem.zahrani@example.com',
                'phone' => '0558901234',
                'type' => 'suggestion',
                'subject' => 'استفسار عن فتح خط جديد',
                'message' => 'هل لديكم خطط لفتح خط من أبها إلى نجران؟ نحتاج هذا الخط بشكل متكرر.',
                'status' => 'pending',
            ],
        ];

        $createdCount = 0;

        foreach ($messages as $messageData) {
            try {
                ContactMessage::create($messageData);

                $createdCount++;
                $this->command->info("✅ تم إنشاء الرسالة: {$messageData['subject']}");

            } catch (\Exception $e) {
                $this->command->error("❌ فشل إنشاء الرسالة: {$messageData['subject']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} رسالة بنجاح!");
    }
}
