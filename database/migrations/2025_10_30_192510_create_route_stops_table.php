<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->onDelete('cascade');
            $table->foreignId('stop_id')->constrained('stops')->onDelete('cascade');
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['route_id', 'order']);
            $table->index('stop_id');

            // Unique constraint - نفس المحطة ما تتكررش في نفس المسار
            $table->unique(['route_id', 'stop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_stops');
    }
};
