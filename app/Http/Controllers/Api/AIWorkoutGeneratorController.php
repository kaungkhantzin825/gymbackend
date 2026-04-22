<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalWorkoutGeneratorService;
use App\Services\GeminiApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIWorkoutGeneratorController extends Controller
{
    protected LocalWorkoutGeneratorService $localGenerator;
    protected GeminiApiService $geminiService;

    public function __construct(
        LocalWorkoutGeneratorService $localGenerator,
        GeminiApiService $geminiService
    ) {
        $this->localGenerator = $localGenerator;
        $this->geminiService  = $geminiService;
    }

    // ─── Custom Plan ─────────────────────────────────────────────────────────

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'fitness_level' => 'required|in:beginner,intermediate,advanced',
            'goal'          => 'required|in:weight_loss,muscle_gain,strength,endurance,flexibility,general_fitness',
            'duration'      => 'required|integer|min:15|max:120',
            'equipment'     => 'nullable|array',
            'equipment.*'   => 'string',
            'focus_areas'   => 'nullable|array',
            'focus_areas.*' => 'string',
            'days_per_week' => 'nullable|integer|min:1|max:7',
        ]);

        $user        = Auth::user();
        $userProfile = $user?->profile;

        $workoutPlan = $this->localGenerator->generate($validated, $userProfile);
        $workoutPlan['plan_name'] = $this->formatGoalName($validated['goal']);

        return response()->json([
            'success'      => true,
            'workout_plan' => $workoutPlan,
            'generated_at' => now()->toISOString(),
        ]);
    }

    // ─── Quick Plan (auto-generated from profile) ─────────────────────────────

    public function quickGenerate(Request $request)
    {
        $user        = Auth::user();
        $userProfile = $user?->profile;

        $fitnessLevel = 'beginner';
        $goal         = 'general_fitness';

        if ($userProfile && $userProfile->target_weight) {
            $currentWeight = (float)($userProfile->weight ?? 70);
            $targetWeight  = (float)$userProfile->target_weight;

            if ($targetWeight < $currentWeight - 2) {
                $goal = 'weight_loss';
            } elseif ($targetWeight > $currentWeight + 2) {
                $goal = 'muscle_gain';
            }
        }

        if ($userProfile && $userProfile->fitness_level) {
            $fitnessLevel = $userProfile->fitness_level;
        }

        $params = [
            'fitness_level' => $fitnessLevel,
            'goal'          => $goal,
            'duration'      => 45,
            'equipment'     => ['bodyweight', 'dumbbells'],
            'focus_areas'   => ['full_body'],
            'days_per_week' => 3,
        ];

        Log::info("AI Workout Quick Generate — user #{$user?->id}, goal: {$goal}, level: {$fitnessLevel}");

        $workoutPlan = $this->localGenerator->generate($params, $userProfile);

        return response()->json([
            'success'      => true,
            'workout_plan' => $workoutPlan,
            'generated_at' => now()->toISOString(),
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function formatGoalName(string $goal): string
    {
        $names = [
            'weight_loss'     => '🔥 Fat Burn & Tone Program',
            'muscle_gain'     => '💪 Hypertrophy Mass Builder',
            'strength'        => '🏋️ Raw Strength Builder',
            'endurance'       => '🏃 Endurance & Conditioning',
            'flexibility'     => '🧘 Mobility & Flexibility Flow',
            'general_fitness' => '⚡ Total Body Fitness Plan',
        ];
        return $names[$goal] ?? 'Personalized Workout Plan';
    }
}
