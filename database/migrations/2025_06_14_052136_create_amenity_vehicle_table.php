<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->decimal('price', 8, 2)->default(0)->comment('سعر الوسيلة للرحلة الواحدة');
            $table->timestamps();

            // Indexes
            $table->index('vehicle_id');
            $table->index('amenity_id');

            // Unique constraint - نفس الوسيلة ما تتكرر لنفس السيارة
            $table->unique(['vehicle_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_amenities');
    }
};
