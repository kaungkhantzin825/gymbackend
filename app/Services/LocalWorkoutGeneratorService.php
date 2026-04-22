<?php

namespace App\Services;

/**
 * Local AI-style workout plan generator.
 * Generates rich, personalized plans entirely on-server — zero external API dependencies.
 */
class LocalWorkoutGeneratorService
{
    // ─── Exercise Library ─────────────────────────────────────────────────────

    private array $exercises = [
        'chest' => [
            'bodyweight' => [
                ['name' => 'Push-Ups',              'beginner' => [3,12,60], 'intermediate' => [4,15,45], 'advanced' => [5,20,30], 'instructions' => 'Keep core tight, lower chest to ground controlled.'],
                ['name' => 'Wide Push-Ups',         'beginner' => [2,10,60], 'intermediate' => [3,12,45], 'advanced' => [4,15,30], 'instructions' => 'Hands wider than shoulders to target outer chest.'],
                ['name' => 'Diamond Push-Ups',      'beginner' => [2,8,75],  'intermediate' => [3,12,60], 'advanced' => [4,15,45], 'instructions' => 'Form a diamond shape with hands to target inner chest and triceps.'],
                ['name' => 'Decline Push-Ups',      'beginner' => [2,10,60], 'intermediate' => [3,12,45], 'advanced' => [4,15,30], 'instructions' => 'Feet elevated, targets upper chest.'],
            ],
            'dumbbells' => [
                ['name' => 'Dumbbell Bench Press',  'beginner' => [3,10,90], 'intermediate' => [4,12,75], 'advanced' => [5,10,60], 'instructions' => 'Plant feet flat, press dumbbells straight up from chest.'],
                ['name' => 'Dumbbell Flyes',        'beginner' => [3,12,75], 'intermediate' => [3,15,60], 'advanced' => [4,15,45], 'instructions' => 'Wide arc motion, slight bend in elbows throughout.'],
                ['name' => 'Incline Dumbbell Press','beginner' => [3,10,90], 'intermediate' => [4,12,75], 'advanced' => [5,12,60], 'instructions' => 'Incline targets upper chest. Press and control the descent.'],
            ],
        ],
        'back' => [
            'bodyweight' => [
                ['name' => 'Superman Hold',         'beginner' => [3,12,60], 'intermediate' => [4,15,45], 'advanced' => [5,15,30], 'instructions' => 'Lie prone, lift chest and legs simultaneously, squeeze glutes.'],
                ['name' => 'Reverse Snow Angels',   'beginner' => [2,10,60], 'intermediate' => [3,15,45], 'advanced' => [4,15,30], 'instructions' => 'Prone position, arms sweep from sides to overhead.'],
            ],
            'pull_up_bar' => [
                ['name' => 'Pull-Ups',              'beginner' => [3,5,90],  'intermediate' => [4,8,75],  'advanced' => [5,12,60], 'instructions' => 'Full dead hang start, chin clears bar. Control descent.'],
                ['name' => 'Chin-Ups',              'beginner' => [3,5,90],  'intermediate' => [4,8,75],  'advanced' => [5,12,60], 'instructions' => 'Underhand grip, pulls more biceps. Full range of motion.'],
                ['name' => 'Hanging Knee Raises',   'beginner' => [3,10,60], 'intermediate' => [4,15,45], 'advanced' => [5,20,30], 'instructions' => 'Hang from bar, drive knees to chest, lower controlled.'],
            ],
            'dumbbells' => [
                ['name' => 'Dumbbell Row',          'beginner' => [3,10,75], 'intermediate' => [4,12,60], 'advanced' => [5,15,45], 'instructions' => 'Brace on bench, drive elbow past your hip, squeeze at top.'],
                ['name' => 'Bent-Over Row',         'beginner' => [3,10,90], 'intermediate' => [4,12,75], 'advanced' => [5,15,60], 'instructions' => 'Hinge at hips 45°, row both dumbbells to lower ribcage.'],
                ['name' => 'Renegade Row',          'beginner' => [3,8,90],  'intermediate' => [3,10,75], 'advanced' => [4,12,60], 'instructions' => 'Push-up position, row one dumbbell at a time, anti-rotation challenge.'],
            ],
        ],
        'shoulders' => [
            'bodyweight' => [
                ['name' => 'Pike Push-Ups',         'beginner' => [3,10,60], 'intermediate' => [4,12,45], 'advanced' => [5,15,30], 'instructions' => 'Inverted V position, lower head toward ground, press back.'],
                ['name' => 'Wall Handstand Hold',   'beginner' => [3,20,90], 'intermediate' => [4,30,75], 'advanced' => [5,45,60], 'instructions' => 'Build shoulder stability through isometric hold against wall.'],
            ],
            'dumbbells' => [
                ['name' => 'Overhead Press',        'beginner' => [3,10,75], 'intermediate' => [4,12,60], 'advanced' => [5,10,45], 'instructions' => 'Press straight overhead, avoid arching lower back.'],
                ['name' => 'Lateral Raises',        'beginner' => [3,12,60], 'intermediate' => [4,15,45], 'advanced' => [5,20,30], 'instructions' => 'Slight bend in elbow, raise to shoulder height, control descent.'],
                ['name' => 'Front Raises',          'beginner' => [3,10,60], 'intermediate' => [3,12,45], 'advanced' => [4,15,30], 'instructions' => 'Alternate arms or both together, raise to eye level.'],
                ['name' => 'Arnold Press',          'beginner' => [3,10,75], 'intermediate' => [4,12,60], 'advanced' => [5,12,45], 'instructions' => 'Start with palms facing you, rotate and press overhead.'],
            ],
        ],
        'triceps' => [
            'bodyweight' => [
                ['name' => 'Tricep Dips',           'beginner' => [3,10,60], 'intermediate' => [4,15,45], 'advanced' => [5,20,30], 'instructions' => 'Hands on chair/bench behind you, lower and press.'],
                ['name' => 'Close-Grip Push-Ups',   'beginner' => [3,10,60], 'intermediate' => [4,12,45], 'advanced' => [5,15,30], 'instructions' => 'Hands shoulder-width, elbows stay close to body.'],
            ],
            'dumbbells' => [
                ['name' => 'Skull Crushers',        'beginner' => [3,12,75], 'intermediate' => [4,12,60], 'advanced' => [5,12,45], 'instructions' => 'Lower dumbbells to temples, press back to start.'],
                ['name' => 'Overhead Tricep Extension', 'beginner' => [3,12,75], 'intermediate' => [4,15,60], 'advanced' => [5,15,45], 'instructions' => 'Both hands on one dumbbell overhead, lower behind head.'],
            ],
        ],
        'biceps' => [
            'dumbbells' => [
                ['name' => 'Bicep Curl',            'beginner' => [3,12,60], 'intermediate' => [4,12,45], 'advanced' => [5,12,30], 'instructions' => 'Elbows tucked to sides, full supination at top.'],
                ['name' => 'Hammer Curl',           'beginner' => [3,12,60], 'intermediate' => [4,12,45], 'advanced' => [5,12,30], 'instructions' => 'Neutral grip (thumbs up), targets brachialis and forearms.'],
                ['name' => 'Concentration Curl',    'beginner' => [3,10,60], 'intermediate' => [3,12,45], 'advanced' => [4,15,30], 'instructions' => 'Elbow on inner thigh, slow isolated curl each arm.'],
            ],
        ],
        'legs' => [
            'bodyweight' => [
                ['name' => 'Bodyweight Squats',     'beginner' => [3,15,60], 'intermediate' => [4,20,45], 'advanced' => [5,25,30], 'instructions' => 'Feet shoulder-width, keep chest up, drive knees out.'],
                ['name' => 'Lunges',                'beginner' => [3,10,60], 'intermediate' => [4,12,45], 'advanced' => [5,15,30], 'instructions' => 'Step forward, knee tracks over toes, lower back knee to floor.'],
                ['name' => 'Jump Squats',           'beginner' => [3,10,75], 'intermediate' => [4,12,60], 'advanced' => [5,15,45], 'instructions' => 'Squat down, explode upward, soft landing, immediately repeat.'],
                ['name' => 'Glute Bridges',         'beginner' => [3,15,45], 'intermediate' => [4,20,30], 'advanced' => [5,25,20], 'instructions' => 'Lie on back, feet flat, drive hips to ceiling, squeeze at top.'],
                ['name' => 'Step-Ups',              'beginner' => [3,10,60], 'intermediate' => [4,12,45], 'advanced' => [5,15,30], 'instructions' => 'Use a sturdy chair/bench, alternate legs.'],
                ['name' => 'Wall Sit',              'beginner' => [3,30,60], 'intermediate' => [4,45,45], 'advanced' => [5,60,30], 'instructions' => 'Back against wall, thighs parallel to floor, hold for time (reps = seconds).'],
            ],
            'dumbbells' => [
                ['name' => 'Goblet Squat',          'beginner' => [3,12,75], 'intermediate' => [4,15,60], 'advanced' => [5,15,45], 'instructions' => 'Hold dumbbell at chest, squat deep keeping elbows inside knees.'],
                ['name' => 'Romanian Deadlift',     'beginner' => [3,12,90], 'intermediate' => [4,12,75], 'advanced' => [5,15,60], 'instructions' => 'Hinge at hips, push them back, keep bar close to legs.'],
                ['name' => 'Dumbbell Lunges',       'beginner' => [3,10,75], 'intermediate' => [4,12,60], 'advanced' => [5,15,45], 'instructions' => 'Hold dumbbells at sides, long stride, back knee almost touches floor.'],
                ['name' => 'Sumo Squat',            'beginner' => [3,12,75], 'intermediate' => [4,15,60], 'advanced' => [5,15,45], 'instructions' => 'Wide stance with toes pointed out, dumbbell between legs.'],
            ],
        ],
        'core' => [
            'bodyweight' => [
                ['name' => 'Plank',                 'beginner' => [3,20,60], 'intermediate' => [4,40,45], 'advanced' => [5,60,30], 'instructions' => 'Forearms and toes, body straight line, squeeze everything.'],
                ['name' => 'Crunches',              'beginner' => [3,15,45], 'intermediate' => [4,20,30], 'advanced' => [5,25,20], 'instructions' => 'Curl shoulders off floor, exhale at top, do not pull neck.'],
                ['name' => 'Leg Raises',            'beginner' => [3,10,60], 'intermediate' => [4,15,45], 'advanced' => [5,20,30], 'instructions' => 'Flat on back, hands under glutes, raise straight legs to 90°.'],
                ['name' => 'Mountain Climbers',     'beginner' => [3,20,60], 'intermediate' => [4,30,45], 'advanced' => [5,40,30], 'instructions' => 'Push-up position, drive knees alternately to chest, fast tempo.'],
                ['name' => 'Russian Twists',        'beginner' => [3,15,60], 'intermediate' => [4,20,45], 'advanced' => [5,25,30], 'instructions' => 'Seated, feet off floor, rotate torso side to side.'],
                ['name' => 'Dead Bug',              'beginner' => [3,8,60],  'intermediate' => [3,12,45], 'advanced' => [4,15,30], 'instructions' => 'Lie on back, opposite arm and leg lower simultaneously, lumbar stays flat.'],
            ],
        ],
        'cardio' => [
            'bodyweight' => [
                ['name' => 'Jumping Jacks',         'beginner' => [3,20,45], 'intermediate' => [4,30,30], 'advanced' => [5,40,20], 'instructions' => 'Full extension of arms and legs, keep rhythm steady.'],
                ['name' => 'High Knees',            'beginner' => [3,20,45], 'intermediate' => [4,30,30], 'advanced' => [5,40,20], 'instructions' => 'Run in place, drive knees high, pump arms.'],
                ['name' => 'Burpees',               'beginner' => [3,8,90],  'intermediate' => [4,12,75], 'advanced' => [5,15,60], 'instructions' => 'Squat, kick back to plank, push-up, jump back in, jump up.'],
                ['name' => 'Box Jumps',             'beginner' => [3,8,75],  'intermediate' => [4,10,60], 'advanced' => [5,12,45], 'instructions' => 'Land soft with bent knees, absorb impact through legs.'],
            ],
        ],
    ];

    // ─── Plan Structures per Goal ─────────────────────────────────────────────

    private array $goalConfig = [
        'weight_loss' => [
            'plan_name' => 'Fat Burn & Tone Program',
            'description' => 'Circuit-style training with minimal rest to maximize calorie burn and preserve muscle mass.',
            'day_structure' => [
                1 => ['focus' => 'Full Body Circuit', 'muscles' => ['chest','legs','core','cardio']],
                2 => ['focus' => 'Cardio & Core',     'muscles' => ['cardio','core']],
                3 => ['focus' => 'Upper Body + HIIT', 'muscles' => ['chest','back','shoulders','cardio']],
                4 => ['focus' => 'Lower Body Burn',   'muscles' => ['legs','core']],
                5 => ['focus' => 'Full Body Finisher','muscles' => ['back','legs','core','cardio']],
            ],
            'rest_multiplier' => 0.7,
            'reps_multiplier' => 1.2,
            'nutrition' => 'Aim for a 300–500 kcal daily deficit. Prioritize high-protein foods (chicken, fish, legumes) to preserve muscle. Eat vegetables with every meal. Stay hydrated with 2–3L of water daily.',
            'tips' => [
                'Keep rest periods short (30–45s) to maintain an elevated heart rate throughout the session.',
                'Add 20 minutes of steady-state cardio (walking/cycling) on rest days for extra calorie burn.',
                'Track your food intake — most people underestimate calories by 30–40%.',
                'Sleep 7–9 hours nightly; poor sleep elevates cortisol and increases fat storage.',
            ],
        ],
        'muscle_gain' => [
            'plan_name' => 'Hypertrophy Mass Builder',
            'description' => 'Progressive overload with compound movements to maximize muscle growth and strength.',
            'day_structure' => [
                1 => ['focus' => 'Chest & Triceps',  'muscles' => ['chest','triceps']],
                2 => ['focus' => 'Back & Biceps',    'muscles' => ['back','biceps']],
                3 => ['focus' => 'Legs & Glutes',    'muscles' => ['legs']],
                4 => ['focus' => 'Shoulders & Arms', 'muscles' => ['shoulders','biceps','triceps']],
                5 => ['focus' => 'Full Body Power',  'muscles' => ['chest','back','legs','core']],
            ],
            'rest_multiplier' => 1.3,
            'reps_multiplier' => 0.9,
            'nutrition' => 'Eat in a 300 kcal surplus daily. Target 1.6–2.2g of protein per kg of body weight. Eat carbs around your workouts for energy and recovery. Do not skip post-workout nutrition within 30 minutes.',
            'tips' => [
                'Prioritize progressive overload — increase weight or reps every week to force adaptation.',
                'Each set should end 1–2 reps before total failure for best hypertrophy stimulus.',
                'Rest 75–120s between sets to allow adequate ATP recovery.',
                'Track your lifts in a notebook or app — what gets measured gets improved.',
            ],
        ],
        'strength' => [
            'plan_name' => 'Raw Strength Builder',
            'description' => 'Low-rep, high-intensity compound movements focused on building functional strength.',
            'day_structure' => [
                1 => ['focus' => 'Push Day',         'muscles' => ['chest','shoulders','triceps']],
                2 => ['focus' => 'Pull Day',         'muscles' => ['back','biceps']],
                3 => ['focus' => 'Leg Day',          'muscles' => ['legs','core']],
                4 => ['focus' => 'Upper Power',      'muscles' => ['chest','back','shoulders']],
                5 => ['focus' => 'Lower Power',      'muscles' => ['legs','core']],
            ],
            'rest_multiplier' => 1.5,
            'reps_multiplier' => 0.7,
            'nutrition' => 'Eat at maintenance or a small surplus. Carbohydrates are your primary fuel for heavy lifts — do not low-carb while strength training. Creatine monohydrate (5g/day) has the strongest evidence for strength gains.',
            'tips' => [
                'Focus on form over weight — a good rep with less weight beats a sloppy rep with more.',
                'Use 3–5 minute rest periods for main compound lifts to allow full neural recovery.',
                'Deload every 4–6 weeks by reducing volume 40% to allow connective tissue to recover.',
                'Sleep is non-negotiable for strength — most neural adaptations happen during deep sleep.',
            ],
        ],
        'endurance' => [
            'plan_name' => 'Endurance & Conditioning',
            'description' => 'High-rep, time-based circuits to build cardiovascular capacity and muscular endurance.',
            'day_structure' => [
                1 => ['focus' => 'Cardio Endurance',  'muscles' => ['cardio','legs']],
                2 => ['focus' => 'Upper Endurance',   'muscles' => ['chest','back','core','cardio']],
                3 => ['focus' => 'Full Body Circuit', 'muscles' => ['legs','core','cardio']],
                4 => ['focus' => 'AMRAP Challenge',   'muscles' => ['chest','back','legs','core']],
                5 => ['focus' => 'Cardio + Core',     'muscles' => ['cardio','core']],
            ],
            'rest_multiplier' => 0.6,
            'reps_multiplier' => 1.5,
            'nutrition' => 'Carbohydrates are essential for endurance. Consume complex carbs (oats, sweet potato, rice) 2–3 hours before training. Rehydrate and replenish electrolytes — especially sodium and potassium — during long sessions.',
            'tips' => [
                'Use the "talk test" to gauge intensity — you should be able to speak in short sentences.',
                'Build volume gradually (10% per week max) to avoid overuse injuries.',
                'Add cross-training (swimming, cycling) to reduce repetitive stress while maintaining fitness.',
                'Focus on breathing — exhale on exertion (the hard part) and inhale on the return.',
            ],
        ],
        'flexibility' => [
            'plan_name' => 'Mobility & Flexibility Flow',
            'description' => 'Functional movement patterns combined with targeted flexibility to improve range of motion.',
            'day_structure' => [
                1 => ['focus' => 'Lower Body Mobility','muscles' => ['legs','core']],
                2 => ['focus' => 'Upper Body Stretch', 'muscles' => ['chest','back','shoulders']],
                3 => ['focus' => 'Core & Hip Flexors', 'muscles' => ['core','legs']],
                4 => ['focus' => 'Full Body Flow',     'muscles' => ['chest','back','legs','core','cardio']],
                5 => ['focus' => 'Active Recovery',    'muscles' => ['core','cardio']],
            ],
            'rest_multiplier' => 0.8,
            'reps_multiplier' => 1.0,
            'nutrition' => 'Anti-inflammatory foods support recovery and flexibility. Include omega-3 rich foods (fatty fish, walnuts, flaxseed). Stay well hydrated — dehydrated muscles are less pliable and more prone to injury.',
            'tips' => [
                'Hold stretches for 30–60 seconds minimum — brief holds do not create lasting change.',
                'Warm up before stretching with 5–10 minutes of light movement to avoid cold muscle tears.',
                'Consistency beats intensity for flexibility — 10 minutes daily beats 60 minutes once a week.',
                'Progress gradually and never stretch to the point of pain, only to mild discomfort.',
            ],
        ],
        'general_fitness' => [
            'plan_name' => 'Total Body Fitness Plan',
            'description' => 'A balanced mix of strength, cardio, and core work to improve overall health and fitness.',
            'day_structure' => [
                1 => ['focus' => 'Upper Body Strength','muscles' => ['chest','back','shoulders']],
                2 => ['focus' => 'Lower Body + Core',  'muscles' => ['legs','core']],
                3 => ['focus' => 'Cardio & Full Body', 'muscles' => ['cardio','chest','legs']],
                4 => ['focus' => 'Arms & Core',        'muscles' => ['biceps','triceps','core']],
                5 => ['focus' => 'Full Body Circuit',  'muscles' => ['chest','back','legs','core','cardio']],
            ],
            'rest_multiplier' => 1.0,
            'reps_multiplier' => 1.0,
            'nutrition' => 'Eat a balanced diet with whole foods. Aim for 0.8–1.2g of protein per kg of body weight. Include a variety of vegetables, fruits, and whole grains. Limit ultra-processed foods and added sugars.',
            'tips' => [
                'Consistency is the single most important factor — 3 average workouts beat 1 perfect one.',
                'Do not skip warm-up and cool-down — they reduce injury risk significantly.',
                'Track progress photos monthly — scale weight fluctuates daily and can be misleading.',
                'Find workouts you enjoy — you will stick to them far longer than ones you dread.',
            ],
        ],
    ];

    // ─── Public API ──────────────────────────────────────────────────────────

    public function generate(array $params, $userProfile = null): array
    {
        $fitnessLevel = $params['fitness_level'] ?? 'beginner';
        $goal         = $params['goal']          ?? 'general_fitness';
        $durationMin  = (int)($params['duration'] ?? 45);
        $daysPerWeek  = (int)($params['days_per_week'] ?? 3);
        $equipment    = $params['equipment']     ?? ['bodyweight'];
        $focusAreas   = $params['focus_areas']   ?? ['full_body'];

        $config  = $this->goalConfig[$goal] ?? $this->goalConfig['general_fitness'];
        $days    = $this->buildSchedule($config, $fitnessLevel, $equipment, $daysPerWeek, $durationMin);
        $overview = $this->buildOverview($config, $fitnessLevel, $daysPerWeek, $durationMin, $userProfile);

        return [
            'plan_name'        => $config['plan_name'],
            'overview'         => $overview,
            'fitness_level'    => $fitnessLevel,
            'goal'             => $goal,
            'weekly_schedule'  => $days,
            'tips'             => $config['tips'],
            'nutrition_advice' => $config['nutrition'],
            'progression_plan' => $this->buildProgressionPlan($goal, $fitnessLevel),
            'generated_by'     => 'Local AI Engine v1',
        ];
    }

    // ─── Internal Builders ────────────────────────────────────────────────────

    private function buildSchedule(array $config, string $level, array $equipment, int $days, int $sessionMin): array
    {
        $schedule      = [];
        $dayStructures = array_values($config['day_structure']);
        $restMult      = $config['rest_multiplier'];
        $repsMult      = $config['reps_multiplier'];

        for ($i = 0; $i < $days; $i++) {
            $dayDef    = $dayStructures[$i % count($dayStructures)];
            $exercises = $this->pickExercises($dayDef['muscles'], $equipment, $level, $sessionMin, $restMult, $repsMult);

            $schedule[] = [
                'day'            => $i + 1,
                'day_name'       => "Day " . ($i + 1) . " — " . $dayDef['focus'],
                'focus'          => $dayDef['focus'],
                'exercises'      => $exercises,
                'total_duration' => $sessionMin,
                'difficulty'     => $level,
            ];
        }

        return $schedule;
    }

    private function pickExercises(array $muscles, array $equipment, string $level, int $sessionMin, float $restMult, float $repsMult): array
    {
        $selected    = [];
        $targetCount = max(4, min(8, (int)($sessionMin / 6)));
        $perMuscle   = max(1, (int)ceil($targetCount / count($muscles)));

        foreach ($muscles as $muscle) {
            $pool = $this->getExercisesForMuscle($muscle, $equipment);
            shuffle($pool);
            $picks = array_slice($pool, 0, $perMuscle);

            foreach ($picks as $ex) {
                $levelData = $ex[$level] ?? $ex['beginner'];
                [$sets, $baseReps, $baseRest] = $levelData;

                $reps = max(1, (int)round($baseReps * $repsMult));
                $rest = max(20, (int)round($baseRest * $restMult));

                $muscles_targeted = $this->getMusclesForGroup($muscle);

                $selected[] = [
                    'name'            => $ex['name'],
                    'sets'            => $sets,
                    'reps'            => "$reps",
                    'rest_seconds'    => $rest,
                    'instructions'    => $ex['instructions'],
                    'target_muscles'  => $muscles_targeted,
                ];

                if (count($selected) >= $targetCount) break 2;
            }
        }

        return $selected;
    }

    private function getExercisesForMuscle(string $muscle, array $equipment): array
    {
        if (!isset($this->exercises[$muscle])) return [];

        $available = [];
        
        // Always include bodyweight exercises
        $available = array_merge($available, $this->exercises[$muscle]['bodyweight'] ?? []);

        // Add equipment-specific exercises
        foreach ($equipment as $eq) {
            if (isset($this->exercises[$muscle][$eq])) {
                $available = array_merge($available, $this->exercises[$muscle][$eq]);
            }
        }

        return $available;
    }

    private function getMusclesForGroup(string $group): array
    {
        $map = [
            'chest'     => ['chest', 'pectorals', 'anterior deltoid'],
            'back'      => ['latissimus dorsi', 'rhomboids', 'trapezius'],
            'shoulders' => ['deltoids', 'rotator cuff'],
            'biceps'    => ['biceps brachii', 'brachialis'],
            'triceps'   => ['triceps brachii'],
            'legs'      => ['quadriceps', 'hamstrings', 'glutes', 'calves'],
            'core'      => ['rectus abdominis', 'obliques', 'transverse abdominis'],
            'cardio'    => ['full body', 'cardiovascular system'],
        ];
        return $map[$group] ?? [$group];
    }

    private function buildOverview(array $config, string $level, int $days, int $duration, $profile): string
    {
        $name    = $profile?->display_name ?? '';
        $intro   = $name ? "Your personalized plan" : "This personalized plan";
        $levelLabel = ['beginner' => 'beginner-friendly', 'intermediate' => 'intermediate', 'advanced' => 'advanced'][$level] ?? $level;

        return "{$intro} is a {$levelLabel} {$config['plan_name']} scheduled across {$days} day(s) per week at {$duration} minutes per session. "
            . $config['description']
            . " Complete each session in order and allow at least one full rest day between training days for optimal recovery.";
    }

    private function buildProgressionPlan(string $goal, string $level): string
    {
        $plans = [
            'weight_loss'    => "Weeks 1–2: Master form and build consistency. Weeks 3–4: Increase reps by 20%. Weeks 5–6: Reduce rest periods by 10 seconds. Weeks 7–8: Add a 4th training day or an extra cardio circuit.",
            'muscle_gain'    => "Weeks 1–2: Establish baseline — track every set. Weeks 3–4: Add 5-10% more weight to main lifts. Weeks 5–6: Increase sets from 3 to 4. Weeks 7–8: Take a deload week, then restart with new maximums.",
            'strength'       => "Weeks 1–2: Practice technique on all compound lifts. Weeks 3–4: Add weight incrementally (2.5–5kg). Weeks 5–6: Increase intensity to 80–85% of max. Weeks 7–8: Deload, test new maxes in Week 9.",
            'endurance'      => "Weeks 1–2: Build aerobic base at conversational pace. Weeks 3–4: Introduce 1 interval session per week. Weeks 5–6: Increase session duration by 10% per week. Weeks 7–8: Add a tempo session — sustained effort at 80%.",
            'flexibility'    => "Weeks 1–2: Daily 10-minute stretch routine. Weeks 3–4: Add dynamic warm-up mobility drills. Weeks 5–6: Increase hold durations to 60 seconds. Weeks 7–8: Introduce yoga flow sequences for active flexibility.",
            'general_fitness'=> "Weeks 1–2: Focus on consistency — do not miss sessions. Weeks 3–4: Increase reps or weight by 5–10%. Weeks 5–6: Add a 4th session (cardio or active recovery). Weeks 7–8: Reassess goals and advance to the next level.",
        ];
        return $plans[$goal] ?? $plans['general_fitness'];
    }
}
