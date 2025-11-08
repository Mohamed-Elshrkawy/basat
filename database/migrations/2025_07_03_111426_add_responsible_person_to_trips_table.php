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
        Schema::table('trips', function (Blueprint $table) {
            $table->string('responsible_person_name')->nullable()->after('payment_status');
            $table->string('responsible_person_id_photo_path')->nullable()->after('responsible_person_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('responsible_person_name');
            $table->dropColumn('responsible_person_id_photo_path');
        });
    }
};
