<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\TutorialVideo;

class AdminDashboardSeeder extends Seeder
{
    public function run()
    {
        // 1. Create a super admin user for the web dashboard if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@gym.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                // 'role' => 'admin' // Enable if your user table supports roles
            ]
        );

        // 2. Demo Tutorial Videos
        $videos = [
            [
                'title' => 'Beginner Full Body Workout',
                'description' => 'A complete 20-minute full body workout suitable for beginners.',
                'video_url' => 'https://www.youtube.com/watch?v=1vRtoEqxNps',
                'gender_target' => 'both',
                'muscle_group' => 'full_body'
            ],
            [
                'title' => 'Advanced Chest & Triceps for Men',
                'description' => 'Build a massive chest with this advanced push day routine.',
                'video_url' => 'https://www.youtube.com/watch?v=rxD321l22T0',
                'gender_target' => 'boy',
                'muscle_group' => 'chest_triceps'
            ],
            [
                'title' => 'Glute Activation & Building',
                'description' => 'Focus on building strong glutes and legs.',
                'video_url' => 'https://www.youtube.com/watch?v=CqS3XwO8n6U',
                'gender_target' => 'girl',
                'muscle_group' => 'legs_glutes'
            ],
            [
                'title' => 'Core Crusher 10-Min Routine',
                'description' => 'Intense 10 minute abs workout for a shredded core.',
                'video_url' => 'https://www.youtube.com/watch?v=dJlFmxiL11s',
                'gender_target' => 'both',
                'muscle_group' => 'core'
            ]
        ];

        foreach ($videos as $video) {
            TutorialVideo::create($video);
        }
    }
}
