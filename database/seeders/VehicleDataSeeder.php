<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\VehicleModel;

class VehicleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brandsWithModels = [
            'تويوتا' => [
                ['name' => 'كوستر', 'seats' => 30],
                ['name' => 'هاي إيس', 'seats' => 14],
                ['name' => 'كوستر ديلوكس', 'seats' => 26],
            ],
            'مرسيدس' => [
                ['name' => 'سبرنتر', 'seats' => 15],
                ['name' => 'سيتي باص', 'seats' => 25],
                ['name' => 'ترافيجو', 'seats' => 35],
            ],
            'هيونداي' => [
                ['name' => 'كاونتي', 'seats' => 32],
                ['name' => 'H350', 'seats' => 17],
                ['name' => 'يونيفرس', 'seats' => 45],
            ],
            'يوتونغ' => [
                ['name' => 'ZK6100', 'seats' => 35],
                ['name' => 'ZK6858', 'seats' => 25],
                ['name' => 'ZK6118', 'seats' => 47],
            ],
            'كينغ لونغ' => [
                ['name' => 'XMQ6900', 'seats' => 33],
                ['name' => 'XMQ6127', 'seats' => 51],
                ['name' => 'XMQ6800', 'seats' => 29],
            ],
            'فورد' => [
                ['name' => 'ترانزيت', 'seats' => 12],
                ['name' => 'ترانزيت ميني باص', 'seats' => 15],
            ],
            'فولفو' => [
                ['name' => '9700', 'seats' => 49],
                ['name' => '9400', 'seats' => 55],
            ],
            'سكانيا' => [
                ['name' => 'توورينغ', 'seats' => 57],
                ['name' => 'إنترلينك', 'seats' => 43],
            ],
            'MAN' => [
                ['name' => 'ليونز كوتش', 'seats' => 51],
                ['name' => 'ليونز سيتي', 'seats' => 39],
            ],
            'إيفيكو' => [
                ['name' => 'ديلي', 'seats' => 19],
                ['name' => 'ماجللي', 'seats' => 31],
            ],
        ];

        foreach ($brandsWithModels as $brandName => $models) {
            // إنشاء الماركة
            $brand = Brand::create([
                'name' => $brandName,
                'is_active' => true,
            ]);

            // إنشاء الموديلات
            foreach ($models as $model) {
                VehicleModel::create([
                    'brand_id' => $brand->id,
                    'name' => $model['name'],
                    'default_seat_count' => $model['seats'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ تم إضافة الماركات والموديلات بنجاح!');
        $this->command->info('📊 إجمالي الماركات: ' . Brand::count());
        $this->command->info('📊 إجمالي الموديلات: ' . VehicleModel::count());
    }
}
