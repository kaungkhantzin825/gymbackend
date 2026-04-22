<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        // Insert default values
        DB::table('app_settings')->insert([
            [
                'key' => 'about',
                'value' => "GymApp - Your Personal Fitness & Nutrition Companion\n\nVersion: 1.0.0\n\nGymApp is your all-in-one fitness and nutrition tracking application powered by AI. Track your meals, workouts, and progress with ease.\n\nFeatures:\n• AI-powered meal scanning\n• Workout tracking\n• Progress analytics\n• Personalized recommendations\n\nDeveloped with ❤️ for fitness enthusiasts",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'privacy_policy',
                'value' => "Privacy & Security Policy\n\nLast Updated: " . now()->format('F d, Y') . "\n\n1. Data Collection\nWe collect information you provide when using our app, including:\n• Account information (name, email)\n• Health data (weight, height, fitness goals)\n• Meal and workout logs\n• Photos you upload\n\n2. Data Usage\nYour data is used to:\n• Provide personalized fitness recommendations\n• Track your progress\n• Improve our services\n\n3. Data Security\nWe implement industry-standard security measures to protect your data:\n• Encrypted data transmission\n• Secure server storage\n• Regular security audits\n\n4. Data Sharing\nWe do NOT sell or share your personal data with third parties.\n\n5. Your Rights\nYou have the right to:\n• Access your data\n• Delete your account\n• Export your data\n\nFor questions, contact us through the Help & Support section.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
