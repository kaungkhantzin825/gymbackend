<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('age')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->decimal('height', 5, 2)->nullable(); // in cm
            $table->decimal('current_weight', 5, 2)->nullable(); // in kg
            $table->decimal('target_weight', 5, 2)->nullable();
            $table->enum('goal', ['lose_weight', 'gain_weight', 'maintain', 'build_muscle'])->default('maintain');
            $table->enum('activity_level', ['sedentary', 'light', 'moderate', 'active', 'very_active'])->default('moderate');
            $table->integer('daily_calorie_target')->nullable();
            $table->integer('daily_protein_target')->nullable(); // in grams
            $table->integer('daily_carbs_target')->nullable();
            $table->integer('daily_fat_target')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
