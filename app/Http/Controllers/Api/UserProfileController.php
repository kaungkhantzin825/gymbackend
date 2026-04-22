<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $profile = $request->user()->profile;

        if (!$profile) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        return response()->json($profile);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $request->user()->id,
            'age' => 'nullable|integer|min:0|max:120',
            'gender' => 'nullable|in:male,female,other',
            'height' => 'nullable|numeric|min:0|max:300',
            'current_weight' => 'nullable|numeric|min:0|max:500',
            'target_weight' => 'nullable|numeric|min:0|max:500',
            'goal' => 'nullable|in:lose_weight,gain_weight,maintain,build_muscle',
            'activity_level' => 'nullable|in:sedentary,light,moderate,active,very_active',
            'daily_calorie_target' => 'nullable|integer|min:0|max:10000',
            'daily_protein_target' => 'nullable|integer|min:0|max:1000',
            'daily_carbs_target' => 'nullable|integer|min:0|max:1000',
            'daily_fat_target' => 'nullable|integer|min:0|max:500',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $user = $request->user();
        
        // Update user name and email if provided
        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        
        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $photo = $request->file('profile_photo');
            $path = $photo->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }
        
        $user->save();

        $profile = $user->profile;

        // Get only the fields that were actually sent
        $profileData = $request->only([
            'age', 'gender', 'height', 'current_weight', 'target_weight',
            'goal', 'activity_level', 'daily_calorie_target',
            'daily_protein_target', 'daily_carbs_target', 'daily_fat_target'
        ]);
        
        // Remove empty values
        $profileData = array_filter($profileData, function($value) {
            return $value !== null && $value !== '';
        });

        if (!$profile) {
            $profile = $user->profile()->create($profileData);
        } else {
            if (!empty($profileData)) {
                $profile->update($profileData);
            }
        }

        // Return user with profile
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo' => $user->profile_photo ? url('storage/' . $user->profile_photo) : null,
            ],
            'profile' => $profile,
        ]);
    }

    /**
     * Calculate recommended calories
     */
    public function calculateCalories(Request $request)
    {
        $profile = $request->user()->profile;

        if (!$profile) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        $tdee = $profile->calculateTDEE();

        if (!$tdee) {
            return response()->json([
                'error' => 'Cannot calculate. Please complete your profile (age, gender, height, weight, activity level)'
            ], 400);
        }

        // Adjust based on goal
        $goalAdjustments = [
            'lose_weight' => -500,  // 500 calorie deficit
            'gain_weight' => 500,   // 500 calorie surplus
            'maintain' => 0,
            'build_muscle' => 300,  // 300 calorie surplus
        ];

        $recommendedCalories = $tdee + ($goalAdjustments[$profile->goal] ?? 0);

        // Calculate macros (example: 40% carbs, 30% protein, 30% fat)
        $protein = round(($recommendedCalories * 0.30) / 4); // 4 cal per gram
        $carbs = round(($recommendedCalories * 0.40) / 4);
        $fat = round(($recommendedCalories * 0.30) / 9); // 9 cal per gram

        return response()->json([
            'bmr' => round($profile->calculateBMR()),
            'tdee' => round($tdee),
            'recommended_calories' => round($recommendedCalories),
            'recommended_protein' => $protein,
            'recommended_carbs' => $carbs,
            'recommended_fat' => $fat,
        ]);
    }
}
