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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // {ar: 'اسم المحطة', en: 'Stop Name'}
            $table->decimal('lat', 10, 8); // خط العرض
            $table->decimal('lng', 11, 8); // خط الطول
            $table->boolean('is_active')->default(true); // حالة المحطة
            $table->timestamps();
            $table->softDeletes(); // للحذف الناعم

            // فهارس للبحث السريع
            $table->index('is_active');
            $table->index(['lat', 'lng']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
