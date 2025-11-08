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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['public_bus', 'private_hire', 'school_service']);
            $table->enum('status', [
                'pending', 'approved', 'on_way', 'completed',
                'cancelled_by_rider', 'cancelled_by_driver', 'cancelled_by_admin'
            ])->default('pending');
            $table->string('pickup_address')->nullable();
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->string('dropoff_address')->nullable();
            $table->decimal('dropoff_lat', 10, 8);
            $table->decimal('dropoff_lng', 11, 8);
            $table->dateTime('trip_datetime');
            $table->boolean('is_round_trip')->default(false);
            $table->dateTime('return_datetime')->nullable();
            $table->decimal('base_fare', 8, 2)->default(0);
            $table->decimal('amenities_fare', 8, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('app_fee_percentage', 5, 2)->default(0);
            $table->decimal('app_fee', 8, 2)->default(0);
            $table->decimal('driver_earning', 8, 2)->default(0);
            $table->decimal('total_fare', 10, 2);
            $table->enum('payment_method', ['wallet', 'cash']);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->nullableMorphs('tripable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
