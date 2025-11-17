<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'عبدالله أحمد السعيد',
                'national_id' => '1201234567',
                'gender' => 'male',
                'phone' => '0551234567',
                'email' => 'abdullah@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 500.00,
            ],
            [
                'name' => 'سارة محمد القحطاني',
                'national_id' => '1212345678',
                'gender' => 'female',
                'phone' => '0552345678',
                'email' => 'sarah@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 750.00,
            ],
            [
                'name' => 'خالد فهد الدوسري',
                'national_id' => '1223456789',
                'gender' => 'male',
                'phone' => '0553456789',
                'email' => 'khaled@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 1000.00,
            ],
            [
                'name' => 'فاطمة عبدالرحمن المطيري',
                'national_id' => '1234567890',
                'gender' => 'female',
                'phone' => '0554567890',
                'email' => 'fatimah@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 300.00,
            ],
            [
                'name' => 'محمد سعد الشمري',
                'national_id' => '1245678901',
                'gender' => 'male',
                'phone' => '0555678901',
                'email' => 'mohammed@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 1500.00,
            ],
            [
                'name' => 'نورة علي الغامدي',
                'national_id' => '1256789012',
                'gender' => 'female',
                'phone' => '0556789012',
                'email' => 'norah@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 600.00,
            ],
            [
                'name' => 'عمر حسن العتيبي',
                'national_id' => '1267890123',
                'gender' => 'male',
                'phone' => '0557890123',
                'email' => 'omar@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 850.00,
            ],
            [
                'name' => 'منى إبراهيم الزهراني',
                'national_id' => '1278901234',
                'gender' => 'female',
                'phone' => '0558901234',
                'email' => 'mona@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 400.00,
            ],
            [
                'name' => 'ياسر ماجد الحربي',
                'national_id' => '1289012345',
                'gender' => 'male',
                'phone' => '0559012345',
                'email' => 'yasser@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 1200.00,
            ],
            [
                'name' => 'ريم خالد القرني',
                'national_id' => '1290123456',
                'gender' => 'female',
                'phone' => '0550123456',
                'email' => 'reem@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 950.00,
            ],
            // والدين (Parents) للأطفال
            [
                'name' => 'عبدالعزيز سليمان الثبيتي',
                'national_id' => '1301234567',
                'gender' => 'male',
                'phone' => '0561234567',
                'email' => 'parent1@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 2000.00,
            ],
            [
                'name' => 'أميرة ناصر السبيعي',
                'national_id' => '1312345678',
                'gender' => 'female',
                'phone' => '0562345678',
                'email' => 'parent2@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 1800.00,
            ],
            [
                'name' => 'فيصل راشد العنزي',
                'national_id' => '1323456789',
                'gender' => 'male',
                'phone' => '0563456789',
                'email' => 'parent3@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 2500.00,
            ],
            // مستخدم بدون رصيد (للاختبار)
            [
                'name' => 'تركي بندر المالكي',
                'national_id' => '1334567890',
                'gender' => 'male',
                'phone' => '0564567890',
                'email' => 'turki@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 0.00,
            ],
            // مستخدم غير مفعل (للاختبار)
            [
                'name' => 'هند سعود الشهراني',
                'national_id' => '1345678901',
                'gender' => 'female',
                'phone' => '0565678901',
                'email' => 'hind@example.com',
                'password' => 'password123',
                'user_type' => 'customer',
                'mobile_verified_at' => now(),
                'is_active'=>1,
                'wallet_balance' => 0.00,
            ],
        ];

        $createdCount = 0;

        foreach ($users as $userData) {
            try {
                // إنشاء المستخدم
                $user = User::create([
                    'name' => $userData['name'],
                    'national_id' => $userData['national_id'],
                    'gender' => $userData['gender'],
                    'phone' => $userData['phone'],
                    'email' => $userData['email'] ?? null,
                    'password' => Hash::make($userData['password']),
                    'user_type' => $userData['user_type'],
                    'mobile_verified_at' => $userData['mobile_verified_at'],
                    'is_active' => true,
                    'status' => 'active',
                ]);

                // إنشاء المحفظة مع رصيد ابتدائي
                $wallet = Wallet::create([
                    'payable_type' => User::class,
                    'payable_id' => $user->id,
                    'balance' => $userData['wallet_balance'],
                    'withdrawal_balance' => 0,
                ]);

                $createdCount++;
                $this->command->info("✅ تم إنشاء المستخدم: {$user->name} (رصيد: {$userData['wallet_balance']} ر.س)");

            } catch (\Exception $e) {
                $this->command->error("❌ فشل إنشاء المستخدم: {$userData['name']}");
                $this->command->error("   السبب: {$e->getMessage()}");
            }
        }

        $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->command->info("✅ تم إنشاء {$createdCount} مستخدم بنجاح!");
    }
}
