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
        Schema::create('trip_station_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_stop_id')->constrained('schedule_stops')->cascadeOnDelete();

            $table->integer('stop_order'); // ترتيب المحطة
            $table->enum('direction', ['outbound', 'return'])->default('outbound');
            $table->enum('status', ['pending', 'arrived', 'departed'])->default('pending');

            // أوقات الوصول والمغادرة الفعلية
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('departed_at')->nullable();

            // عدد الركاب النازلين والطالعين
            $table->integer('passengers_boarded')->default(0);
            $table->integer('passengers_alighted')->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['trip_instance_id', 'stop_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_station_progress');
    }
};
