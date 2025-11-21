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
            $table->decimal('app_fees', 10, 2)->nullable()->after('total_amount')->comment('مستحقات التطبيق (العمولة)');
            $table->decimal('driver_earnings', 10, 2)->nullable()->after('app_fees')->comment('صافي أرباح السائق');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['app_fees', 'driver_earnings']);
        });
    }
};
