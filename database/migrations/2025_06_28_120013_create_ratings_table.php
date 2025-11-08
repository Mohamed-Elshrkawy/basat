<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_id')->comment('User who is giving the rating')->constrained('users')->cascadeOnDelete();
            $table->foreignId('rated_id')->comment('User who is being rated')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // Rating from 1 to 5
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['trip_id', 'rater_id', 'rated_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};