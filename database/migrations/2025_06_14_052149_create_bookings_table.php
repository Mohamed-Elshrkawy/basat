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
            $table->string('booking_number')->unique(); // رقم الحجز
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // الراكب
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete(); // الرحلة
            $table->date('travel_date'); // تاريخ السفر

            // معلومات الحجز
            $table->enum('trip_type', ['one_way', 'round_trip'])->default('one_way');
            $table->integer('number_of_seats'); // عدد المقاعد
            $table->json('seat_numbers'); // أرقام المقاعد المحجوزة

            // معلومات الأسعار
            $table->decimal('outbound_fare', 10, 2); // سعر الذهاب
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
                'refunded'      // تم الاسترداد
            ])->default('pending');

            // ملاحظات
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['schedule_id', 'travel_date']);
            $table->index('booking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
