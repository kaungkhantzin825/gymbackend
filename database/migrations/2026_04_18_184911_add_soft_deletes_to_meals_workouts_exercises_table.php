<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to meals
        Schema::table('meals', function (Blueprint $table) {
            if (!Schema::hasColumn('meals', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to workouts
        Schema::table('workouts', function (Blueprint $table) {
            if (!Schema::hasColumn('workouts', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to workout_sets
        Schema::table('workout_sets', function (Blueprint $table) {
            if (!Schema::hasColumn('workout_sets', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add soft deletes to exercises
        Schema::table('exercises', function (Blueprint $table) {
            if (!Schema::hasColumn('exercises', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        foreach (['meals', 'workouts', 'workout_sets', 'exercises'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
