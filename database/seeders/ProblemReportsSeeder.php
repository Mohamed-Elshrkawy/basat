<?php

namespace Database\Seeders;

use App\Models\ProblemReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProblemReportsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('ℹ️ تم تخطي ProblemReportsSeeder - يحتاج إلى trip_id والذي يتطلب وجود trips');
        // البنية الحالية للجدول تتطلب trip_id وهو إلزامي
        // سيتم تفعيل هذا السيدر لاحقاً عندما يتم إنشاء trips
    }
}
