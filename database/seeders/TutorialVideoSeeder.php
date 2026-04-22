<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TutorialVideoSeeder extends Seeder
{
    public function run(): void
    {
        $videos = [
            // Men - Strength
            ['title' => 'Barbell Squat – Beginner to Advanced', 'description' => 'Master the king of all exercises. Learn proper form, depth, and breathing. Suitable for all levels.', 'category' => 'Strength', 'gender' => 'male', 'duration' => '12 min', 'thumbnail_path' => null, 'video_url' => 'https://www.youtube.com/watch?v=IhCBlJFSTX4', 'is_active' => true],
            ['title' => 'Bench Press Full Tutorial', 'description' => 'Build a bigger chest with the correct bench press technique. Includes grip width, bar path, and arch.', 'category' => 'Strength', 'gender' => 'male', 'duration' => '10 min', 'thumbnail_path' => null, 'video_url' => 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'is_active' => true],
            ['title' => 'Deadlift – Perfect Form Guide', 'description' => 'Step-by-step deadlift guide covering conventional, sumo stance and the most common mistakes to avoid.', 'category' => 'Strength', 'gender' => 'male', 'duration' => '15 min', 'thumbnail_path' => null, 'video_url' => 'https://www.youtube.com/watch?v=op9kVnSso6Q', 'is_active' => true],
            ['title' => 'Pull-Up Masterclass', 'description' => 'From zero to multiple pull-ups. Progressions, grip variations and lat activation cues included.', 'category' => 'Strength', 'gender' => 'male', 'duration' => '8 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/pullup', 'is_active' => true],
            ['title' => 'Overhead Press Technique', 'description' => 'Press big weight overhead safely. Foot positioning, core bracing and lockout explained in detail.', 'category' => 'Strength', 'gender' => 'male', 'duration' => '9 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/ohp', 'is_active' => true],

            // Women - Strength / Glutes
            ['title' => 'Hip Thrust Guide for Women', 'description' => 'Build powerful glutes with the hip thrust. Setup, foot placement, and mind-muscle connection tips.', 'category' => 'Strength', 'gender' => 'female', 'duration' => '11 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/hipthrust', 'is_active' => true],
            ['title' => 'Romanian Deadlift – Glute Focus', 'description' => 'Target your posterior chain effectively. Perfect for women looking to sculpt their hamstrings and glutes.', 'category' => 'Strength', 'gender' => 'female', 'duration' => '8 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/rdl', 'is_active' => true],
            ['title' => 'Dumbbell Full Body Women\'s Workout', 'description' => '30-minute dumbbell workout targeting all major muscle groups. No equipment other than a pair of dumbbells needed.', 'category' => 'Strength', 'gender' => 'female', 'duration' => '30 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/dbbfull', 'is_active' => true],

            // Cardio
            ['title' => 'HIIT Cardio – Burn Fat Fast (Men)', 'description' => '20-minute high intensity interval training. No equipment needed. 3:1 work-to-rest ratio for maximum calorie burn.', 'category' => 'HIIT', 'gender' => 'male', 'duration' => '20 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/hiit-men', 'is_active' => true],
            ['title' => 'Beginner Cardio for Women', 'description' => 'Low-impact cardio session ideal for beginners. Get your heart rate up without stressing the joints.', 'category' => 'Cardio', 'gender' => 'female', 'duration' => '25 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/cardio-women', 'is_active' => true],
            ['title' => 'Jump Rope HIIT Workout', 'description' => 'Tabata-style jump rope session. Burns up to 500 calories. Works for both men and women at any level.', 'category' => 'HIIT', 'gender' => 'male', 'duration' => '15 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/jumprope', 'is_active' => true],

            // Yoga / Flexibility
            ['title' => 'Morning Yoga Flow – 20 Minutes', 'description' => 'Start your day with this energizing yoga flow. Improves flexibility, posture and sets a positive tone.', 'category' => 'Flexibility', 'gender' => 'female', 'duration' => '20 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/yoga-am', 'is_active' => true],
            ['title' => 'Full Body Stretch & Recovery', 'description' => 'Essential post-workout stretching routine. Reduces DOMS and improves range of motion over time.', 'category' => 'Flexibility', 'gender' => 'male', 'duration' => '18 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/stretch', 'is_active' => true],
            ['title' => 'Hip Flexor & Lower Back Relief', 'description' => 'Perfect for desk workers and gym goers. Targeted mobility work for tight hips and lower back.', 'category' => 'Flexibility', 'gender' => 'female', 'duration' => '14 min', 'thumbnail_path' => null, 'video_url' => 'https://youtu.be/hipflex', 'is_active' => true],
        ];

        foreach ($videos as $video) {
            DB::table('tutorial_videos')->insertOrIgnore(array_merge($video, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
