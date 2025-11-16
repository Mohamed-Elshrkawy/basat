<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // حالة حضور الراكب
            $table->enum('passenger_status', [
                'pending',      // لم يحضر بعد
                'checked_in',   // حضر (تم المسح)
                'boarded',      // صعد الباص
                'completed',    // وصل الوجهة
                'no_show'       // لم يحضر
            ])->default('pending')->after('status');

            // أوقات مهمة
            $table->timestamp('checked_in_at')->nullable()->after('passenger_status');
            $table->timestamp('boarded_at')->nullable()->after('checked_in_at');
            $table->timestamp('arrived_at')->nullable()->after('boarded_at');

            // المحطة التي صعد منها
            $table->foreignId('boarding_stop_id')->nullable()->constrained('schedule_stops')->after('arrived_at');

            // ملاحظات السائق
            $table->text('driver_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'passenger_status',
                'checked_in_at',
                'boarded_at',
                'arrived_at',
                'boarding_station_id',
                'driver_notes'
            ]);
        });
    }
};
