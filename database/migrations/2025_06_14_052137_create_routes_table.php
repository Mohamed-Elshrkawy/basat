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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->json('name')->nullable();
            $table->json('start_point_name')->nullable();
            $table->json('end_point_name')->nullable();
            $table->decimal('range_km', 8, 2)->nullable();
            $table->foreignId('start_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('end_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('start_city_id');
            $table->index('end_city_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
