<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->foreignId('stop_id')->constrained('stops')->onDelete('cascade');

            // نوع الوقت: ذهاب أو عودة
            $table->enum('direction', ['outbound', 'return'])->default('outbound')->comment('outbound=ذهاب, return=عودة');

            // الأوقات
            $table->time('arrival_time')->nullable()->comment('وقت الوصول للمحطة');
            $table->time('departure_time')->nullable()->comment('وقت المغادرة من المحطة');

            // الترتيب
            $table->integer('order')->default(0)->comment('ترتيب المحطة في المسار');

            $table->timestamps();

            // Indexes
            $table->index(['schedule_id', 'direction', 'order']);
            $table->index('stop_id');

            // Unique constraint - نفس المحطة ما تتكررش في نفس الرحلة ونفس الاتجاه
            $table->unique(['schedule_id', 'stop_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_stops');
    }
};
