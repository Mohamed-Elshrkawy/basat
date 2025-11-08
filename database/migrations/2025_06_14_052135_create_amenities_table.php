<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->json('name')->comment('اسم الوسيلة (متعدد اللغات)');
            $table->string('icon')->nullable()->comment('أيقونة الوسيلة (heroicon)');
            $table->json('description')->nullable()->comment('وصف الوسيلة (متعدد اللغات)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
