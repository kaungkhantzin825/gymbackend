<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $date = $request->date ?? Carbon::today()->toDateString();

        // Get today's meals
        $meals = $user->meals()
            ->with('foodLogs')
            ->whereDate('meal_time', $date)
            ->get();

        // Calculate totals
        $totalCalories = $meals->sum('total_calories');
        $totalProtein = $meals->sum('total_protein');
        $totalCarbs = $meals->sum('total_carbs');
        $totalFat = $meals->sum('total_fat');

        // Get today's exercises
        $exercises = $user->exercises()
            ->whereDate('exercise_time', $date)
            ->get();

        $totalCaloriesBurned = $exercises->sum('calories_burned');

        // Get user profile and targets
        $profile = $user->profile;
        $targets = [
            'calories' => $profile?->daily_calorie_target ?? 2000,
            'protein' => $profile?->daily_protein_target ?? 150,
            'carbs' => $profile?->daily_carbs_target ?? 200,
            'fat' => $profile?->daily_fat_target ?? 65,
        ];

        // Net calories (consumed - burned)
        $netCalories = $totalCalories - $totalCaloriesBurned;

        // Get latest weight
        $latestWeight = $user->weightLogs()
            ->orderBy('logged_at', 'desc')
            ->first();

        return response()->json([
            'date' => $date,
            'total_calories_consumed' => round($totalCalories, 1),
            'total_calories_burned' => round($totalCaloriesBurned, 1),
            'net_calories' => round($netCalories, 1),
            'total_protein' => round($totalProtein, 1),
            'total_carbs' => round($totalCarbs, 1),
            'total_fat' => round($totalFat, 1),
            'meals_count' => $meals->count(),
            'exercises_count' => $exercises->count(),
            'calorie_target' => $targets['calories'],
            'protein_target' => $targets['protein'],
            'carbs_target' => $targets['carbs'],
            'fat_target' => $targets['fat'],
            'current_weight' => $latestWeight ? $latestWeight->weight : null,
        ]);
    }

    /**
     * Get weekly summary
     */
    public function weekly(Request $request)
    {
        $user = $request->user();
        $endDate = $request->end_date ?? Carbon::today();
        $startDate = Carbon::parse($endDate)->subDays(6);

        $summary = [];

        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $dateStr = $date->toDateString();
            
            $meals = $user->meals()->whereDate('meal_time', $dateStr)->get();
            $exercises = $user->exercises()->whereDate('exercise_time', $dateStr)->get();

            $summary[] = [
                'date' => $dateStr,
                'calories_consumed' => $meals->sum('total_calories'),
                'calories_burned' => $exercises->sum('calories_burned'),
                'protein' => round($meals->sum('total_protein'), 1),
                'carbs' => round($meals->sum('total_carbs'), 1),
                'fat' => round($meals->sum('total_fat'), 1),
            ];
        }

        return response()->json($summary);
    }
}
