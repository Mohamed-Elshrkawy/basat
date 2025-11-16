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
        Schema::create('trip_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->date('trip_date');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');

            // معلومات البداية والنهاية
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // ملاحظات السائق
            $table->text('driver_notes')->nullable();

            $table->timestamps();

            // فهرسة
            $table->unique(['schedule_id', 'trip_date']);
            $table->index(['trip_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_instances');
    }
};
