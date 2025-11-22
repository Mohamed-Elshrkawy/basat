<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // الإعدادات والبيانات الأساسية
            RolesAndPermissionsSeeder::class,
            SettingsSeeder::class,
            StaticPageSeeder::class,
            FaqSeeder::class,

            // البيانات الجغرافية
            CitySeeder::class,

            // بيانات المركبات والوسائل
            VehicleDataSeeder::class,
            AmenitiesSeeder::class,

            // المحطات والمسارات
            StopsSeeder::class,

            // المستخدمين والسائقين
            UsersSeeder::class,
            DriversSeeder::class, // سائقو الحافلات العامة
            PrivateDriversSeeder::class, // سائقو الحافلات الخاصة
            SchoolDriversSeeder::class, // سائقو الحافلات المدرسية

            // المسارات والجداول (يحتاج سائقين)
            RoutesSeeder::class,

            // المدارس والأطفال
            SchoolPackagesSeeder::class,
            SchoolsSeeder::class,
            ChildrenSeeder::class,

            // الحجوزات (يحتاج مستخدمين وجداول)
            BookingsSeeder::class,

            // التقييمات (يحتاج حجوزات)
            RatingsSeeder::class,

            // التقارير والرسائل
            ProblemReportsSeeder::class,
            ContactMessagesSeeder::class,
        ]);
    }
}
