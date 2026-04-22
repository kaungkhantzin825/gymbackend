<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutorial_videos', function (Blueprint $table) {
            if (!Schema::hasColumn('tutorial_videos', 'category'))
                $table->string('category')->nullable()->after('description');
            if (!Schema::hasColumn('tutorial_videos', 'gender'))
                $table->string('gender')->nullable()->after('category');  // male / female / both
            if (!Schema::hasColumn('tutorial_videos', 'duration'))
                $table->string('duration')->nullable()->after('gender');
            if (!Schema::hasColumn('tutorial_videos', 'thumbnail_path'))
                $table->string('thumbnail_path')->nullable()->after('duration');
            if (!Schema::hasColumn('tutorial_videos', 'is_active'))
                $table->boolean('is_active')->default(true)->after('thumbnail_path');
        });
    }

    public function down(): void
    {
        Schema::table('tutorial_videos', function (Blueprint $table) {
            $table->dropColumn(['category', 'gender', 'duration', 'thumbnail_path', 'is_active']);
        });
    }
};
