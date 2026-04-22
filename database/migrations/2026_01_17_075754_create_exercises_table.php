<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('exercise_name');
            $table->enum('exercise_type', ['cardio', 'strength', 'flexibility', 'sports'])->default('cardio');
            $table->integer('duration_minutes')->nullable(); // for cardio
            $table->integer('calories_burned')->default(0);
            $table->integer('sets')->nullable(); // for strength
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable(); // kg
            $table->text('notes')->nullable();
            $table->timestamp('exercise_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
