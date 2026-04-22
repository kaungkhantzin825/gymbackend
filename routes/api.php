<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\WeightLogController;
use App\Http\Controllers\Api\FoodSearchController;
use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\TutorialVideoController;
use App\Http\Controllers\Api\PhotoGalleryController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AICoachController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/videos', [TutorialVideoController::class, 'index']); // Public videos

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/weekly', [DashboardController::class, 'weekly']);

    // User Profile
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile', [UserProfileController::class, 'update']);
    Route::post('/profile', [UserProfileController::class, 'update']); // For multipart form data
    Route::patch('/profile', [UserProfileController::class, 'update']); // For multipart form data
    Route::get('/profile/calculate-calories', [UserProfileController::class, 'calculateCalories']);

    // Meals
    Route::get('/meals/history', [MealController::class, 'history']);
    Route::post('/meals/{id}/restore', [MealController::class, 'restore']);
    Route::apiResource('meals', MealController::class);
    Route::post('/meals/{meal}/foods', [MealController::class, 'addFood']);
    Route::delete('/meals/{meal}/foods/{foodLog}', [MealController::class, 'removeFood']);
    Route::post('/meals/{meal}/analyze', [MealController::class, 'analyzePhoto']);

    // Tutorial Videos (authenticated)
    Route::get('/videos', [TutorialVideoController::class, 'index']);
    Route::get('/videos/{id}', [TutorialVideoController::class, 'show']);


    // Exercises
    Route::apiResource('exercises', ExerciseController::class);

    // Weight Logs
    Route::apiResource('weight-logs', WeightLogController::class);

    // Food Search (FatSecret API)
    Route::get('/food/search', [FoodSearchController::class, 'search']);
    Route::get('/food/{foodId}', [FoodSearchController::class, 'show']);
    Route::post('/food/barcode', [FoodSearchController::class, 'barcode']);
    Route::get('/food/autocomplete', [FoodSearchController::class, 'autocomplete']);

    // AI Coach Features
    Route::post('/ai/workout/generate', [AICoachController::class, 'generateWorkout']);
    Route::post('/ai/coach/chat', [AICoachController::class, 'chat']); // Placeholder

    // AI Workout Generator (New Feature)
    Route::post('/ai/workout-plan/generate', [\App\Http\Controllers\Api\AIWorkoutGeneratorController::class, 'generate']);
    Route::post('/ai/workout-plan/quick-generate', [\App\Http\Controllers\Api\AIWorkoutGeneratorController::class, 'quickGenerate']);

    // Workout Tracker
    Route::apiResource('workouts', WorkoutController::class);
    Route::post('/workouts/{workout}/sets', [WorkoutController::class, 'addSet']);
    Route::put('/workouts/{workout}/complete', [WorkoutController::class, 'completeWorkout']);

    // Photo Gallery
    Route::apiResource('gallery', PhotoGalleryController::class)->only(['index', 'store', 'destroy']);

    // Analytics Report
    Route::get('/analytics/report', [AnalyticsController::class, 'report']);

    // Push Notifications
    Route::post('/notifications/register-device', [\App\Http\Controllers\Api\NotificationController::class, 'registerDevice']);
    Route::post('/notifications/test', [\App\Http\Controllers\Api\NotificationController::class, 'sendTestNotification']);

    // Support / Help & Contact
    Route::post('/support/send', [\App\Http\Controllers\Api\SupportController::class, 'sendMessage']);
    Route::get('/support/messages', [\App\Http\Controllers\Api\SupportController::class, 'getMessages']);
    Route::get('/support/messages/{id}', [\App\Http\Controllers\Api\SupportController::class, 'getMessage']);

    // App Settings (About, Privacy Policy)
    Route::get('/settings/about', [\App\Http\Controllers\Api\AppSettingsController::class, 'getAbout']);
    Route::get('/settings/privacy-policy', [\App\Http\Controllers\Api\AppSettingsController::class, 'getPrivacyPolicy']);

    // Admin Routes (requires admin role)
    Route::prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        
        // User Management
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
        
        // Meal/Post Management
        Route::get('/meals', [AdminController::class, 'getMeals']);
        Route::put('/meals/{id}', [AdminController::class, 'updateMeal']);
        Route::delete('/meals/{id}', [AdminController::class, 'deleteMeal']);
        
        // Tutorial Video Management
        Route::get('/videos', [AdminController::class, 'getVideos']);
        Route::post('/videos', [AdminController::class, 'createVideo']);
        Route::put('/videos/{id}', [AdminController::class, 'updateVideo']);
        Route::delete('/videos/{id}', [AdminController::class, 'deleteVideo']);
        
        // Reports
        Route::post('/reports/generate', [AdminController::class, 'generateReport']);
        Route::post('/reports/export', [AdminController::class, 'exportReport']);
    });
});
