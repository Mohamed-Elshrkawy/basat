<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Booking type
            $table->enum('type', ['public_bus', 'private_bus', 'school_bus'])->default('public_bus');

            $table->string('booking_number')->unique(); // رقم الحجز
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // الراكب

            // For public bus
            $table->foreignId('schedule_id')->nullable()->constrained()->cascadeOnDelete(); // الرحلة

            // For private bus
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('start_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('end_city_id')->nullable()->constrained('cities')->nullOnDelete();

            // Travel dates
            $table->date('travel_date'); // تاريخ السفر
            $table->date('return_date')->nullable(); // تاريخ العودة

            // معلومات الحجز
            $table->enum('trip_type', ['one_way', 'round_trip'])->default('one_way');
            $table->integer('number_of_seats'); // عدد المقاعد
            $table->json('seat_numbers')->nullable(); // أرقام المقاعد المحجوزة

            // محطات الصعود والنزول (للحافلات العامة) - سيتم إضافة foreign keys في migration منفصل
            $table->unsignedBigInteger('outbound_boarding_stop_id')->nullable();
            $table->unsignedBigInteger('outbound_dropping_stop_id')->nullable();
            $table->unsignedBigInteger('return_boarding_stop_id')->nullable();
            $table->unsignedBigInteger('return_dropping_stop_id')->nullable();

            // Distance and pricing for private trips
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('amenities_cost', 10, 2)->default(0);
            $table->integer('total_days')->default(1);

            // معلومات الأسعار للرحلات العامة
            $table->decimal('outbound_fare', 10, 2)->nullable(); // سعر الذهاب
            $table->decimal('return_fare', 10, 2)->nullable(); // سعر العودة
            $table->decimal('discount', 10, 2)->default(0); // الخصم
            $table->decimal('total_amount', 10, 2); // المبلغ الإجمالي

            // معلومات الدفع
            $table->enum('payment_method', ['cash', 'card', 'wallet', 'bank_transfer']); // طريقة الدفع
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable(); // رقم المعاملة
            $table->timestamp('paid_at')->nullable();

            // حالة الحجز
            $table->enum('status', [
                'pending',      // في انتظار الدفع
                'confirmed',    // مؤكد
                'cancelled',    // ملغي
                'completed',    // مكتمل
                'in_progress',  // جاري التنفيذ (للرحلات الخاصة)
                'refunded'      // تم الاسترداد
            ])->default('pending');

            // Trip status for private trips
            $table->enum('trip_status', ['pending', 'started', 'completed', 'cancelled'])->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // حالة حضور الراكب (للحافلات العامة)
            $table->enum('passenger_status', [
                'pending',      // لم يحضر بعد
                'checked_in',   // حضر (تم المسح)
                'boarded',      // صعد الباص
                'completed',    // وصل الوجهة
                'no_show'       // لم يحضر
            ])->default('pending');

            // أوقات مهمة
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('boarded_at')->nullable();
            $table->timestamp('arrived_at')->nullable();

            // ملاحظات
            $table->text('notes')->nullable();
            $table->text('driver_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('type');
            $table->index(['user_id', 'status']);
            $table->index(['schedule_id', 'travel_date']);
            $table->index('booking_number');
            $table->index('status');
            $table->index('payment_status');
            $table->index('travel_date');
            $table->index(['driver_id', 'travel_date']);
            $table->index(['start_city_id', 'end_city_id']);
        });

        // Create pivot table for booking amenities
        Schema::create('booking_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['booking_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_amenities');
        Schema::dropIfExists('bookings');
    }
};
