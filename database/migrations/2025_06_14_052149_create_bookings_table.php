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
            $table->string('booking_number')->unique()->comment('رقم الحجز الفريد');
            $table->foreignId('passenger_id')->constrained('users')->comment('الراكب');
            $table->foreignId('schedule_id')->constrained('schedules')->comment('الرحلة');
            $table->enum('trip_direction', ['outbound', 'return'])->default('outbound')->comment('اتجاه الرحلة');
            $table->json('seat_numbers')->comment('أرقام المقاعد المحجوزة');
            $table->integer('total_seats')->comment('عدد المقاعد');

            // الأسعار
            $table->decimal('fare_amount', 10, 2)->comment('سعر التذكرة الأساسي');
            $table->decimal('amenities_amount', 10, 2)->default(0)->comment('سعر الوسائل الإضافية');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('قيمة الخصم');
            $table->decimal('total_amount', 10, 2)->comment('المبلغ الإجمالي');

            // معلومات الدفع
            $table->enum('payment_method', ['cash', 'credit_card', 'apple_pay', 'stc_pay', 'mada'])->comment('طريقة الدفع');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->comment('حالة الدفع');
            $table->string('payment_transaction_id')->nullable()->comment('رقم عملية الدفع');
            $table->timestamp('paid_at')->nullable()->comment('وقت الدفع');

            // حالة الحجز
            $table->enum('status', ['confirmed', 'cancelled', 'completed', 'no_show'])->default('confirmed')->comment('حالة الحجز');
            $table->text('cancellation_reason')->nullable()->comment('سبب الإلغاء');
            $table->timestamp('cancelled_at')->nullable()->comment('وقت الإلغاء');

            // معلومات إضافية
            $table->json('selected_amenities')->nullable()->comment('الوسائل المختارة');
            $table->text('notes')->nullable()->comment('ملاحظات');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_number');
            $table->index('passenger_id');
            $table->index('schedule_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('trip_direction');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
