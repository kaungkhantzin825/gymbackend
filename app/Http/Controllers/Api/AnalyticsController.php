<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function report(Request $request)
    {
        $user = $request->user();
        
        $meals = $user->meals()->where('meal_time', '>=', now()->subDays(30))->get();
        $workouts = $user->exercises()->where('date', '>=', now()->subDays(30))->get();
        
        // Summarize data
        $totalCaloriesConsumed = $meals->sum('total_calories');
        $totalCaloriesBurned = $workouts->sum('calories_burned');
        
        $report = [
            'total_calories_consumed_30d' => $totalCaloriesConsumed,
            'total_calories_burned_30d' => $totalCaloriesBurned,
            'net_calories' => $totalCaloriesConsumed - $totalCaloriesBurned,
            'workouts_count_30d' => $workouts->count(),
            'meals_logged_count_30d' => $meals->count(),
        ];

        return response()->json($report);
    }
}
