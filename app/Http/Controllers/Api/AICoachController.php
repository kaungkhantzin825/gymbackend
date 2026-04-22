<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\GeminiApiService;

class AICoachController extends Controller
{
    protected GeminiApiService $aiService;

    public function __construct(GeminiApiService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generateWorkout(Request $request)
    {
        $request->validate([
            'equipment_available' => 'required|string',
            'time_available_minutes' => 'required|integer',
            'focus_area' => 'required|string',
        ]);

        $user = $request->user();
        $profile = $user->profile?->toArray() ?? [];

        $plan = $this->aiService->generateWorkoutPlan($profile, $request->only([
            'equipment_available',
            'time_available_minutes',
            'focus_area',
            'injuries'
        ]));

        return response()->json($plan);
    }
}
