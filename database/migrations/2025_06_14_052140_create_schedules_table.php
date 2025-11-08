<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();

            // نوع الرحلة: ذهاب فقط أو ذهاب وعودة
            $table->enum('trip_type', ['one_way', 'round_trip'])->default('one_way');

            // أوقات الذهاب (من أول محطة لآخر محطة)
            $table->time('departure_time')->comment('وقت المغادرة من أول محطة - الذهاب');
            $table->time('arrival_time')->comment('وقت الوصول لآخر محطة - الذهاب');

            // أوقات العودة (إذا كانت round_trip)
            $table->time('return_departure_time')->nullable()->comment('وقت المغادرة من أول محطة - العودة');
            $table->time('return_arrival_time')->nullable()->comment('وقت الوصول لآخر محطة - العودة');

            // التسعير
            $table->decimal('fare', 8, 2)->comment('سعر التذكرة - ذهاب');
            $table->decimal('return_fare', 8, 2)->nullable()->comment('سعر التذكرة - عودة');
            $table->decimal('round_trip_discount', 8, 2)->nullable()->comment('قيمة الخصم عند شراء ذهاب وعودة');

            // أيام التشغيل
            $table->json('days_of_week')->comment('أيام الأسبوع المتاحة ["Monday", "Tuesday", ...]');

            // معلومات إضافية
            $table->integer('available_seats')->default(50)->comment('عدد المقاعد المتاحة');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes للأداء
            $table->index('route_id');
            $table->index('driver_id');
            $table->index('trip_type');
            $table->index('is_active');
            $table->index(['route_id', 'is_active']);
            $table->index(['route_id', 'trip_type', 'departure_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
