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
        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->integer('set_number')->default(1);
            $table->integer('reps')->default(0);
            $table->decimal('weight', 8, 2)->default(0); // in kg or lbs
            $table->integer('rpe')->nullable(); // Rate of Perceived Exertion (1-10)
            $table->integer('rest_time_seconds')->nullable();
            $table->boolean('is_superset')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
    }
};
