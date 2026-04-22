<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\FoodLog;
use App\Services\FoodImageRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MealController extends Controller
{
    protected $imageRecognitionService;

    public function __construct(FoodImageRecognitionService $imageRecognitionService)
    {
        $this->imageRecognitionService = $imageRecognitionService;
    }

    public function index(Request $request)
    {
        $query = $request->user()->meals()->with('foodLogs');

        if ($request->has('date')) {
            $date = $request->date;
            $query->whereDate('meal_time', $date);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('meal_time', [$request->start_date, $request->end_date]);
        }

        $meals = $query->orderBy('meal_time', 'desc')->paginate(20);

        return response()->json($meals);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'meal_time' => 'required|date',
            'photo' => 'nullable|image|max:5120', // 5MB max
            'notes' => 'nullable|string',
            'foods' => 'nullable|array',
            'foods.*.food_name' => 'required|string',
            'foods.*.calories' => 'required|integer',
            'foods.*.serving_size' => 'required|numeric',
            'foods.*.serving_unit' => 'required|string',
        ]);

        $photoPath = null;
        $detectedFoods = null;
        
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('meals', 'public');
            
            // Analyze image for food detection
            $fullPath = storage_path('app/public/' . $photoPath);
            $detectedFoods = $this->imageRecognitionService->analyzeImage($fullPath);
        }

        $meal = $request->user()->meals()->create([
            'name' => $request->name,
            'meal_time' => $request->meal_time,
            'photo_path' => $photoPath,
            'notes' => $request->notes,
        ]);

        // Add food logs if provided manually
        if ($request->has('foods')) {
            foreach ($request->foods as $food) {
                $meal->foodLogs()->create($food);
            }
            $meal->recalculateTotals();
        }
        // Or add detected foods from AI
        elseif ($detectedFoods && isset($detectedFoods['detected_foods'])) {
            foreach ($detectedFoods['detected_foods'] as $food) {
                $meal->foodLogs()->create($food);
            }
            $meal->recalculateTotals();
        }

        $response = $meal->load('foodLogs')->toArray();
        
        // Include AI detection results
        if ($detectedFoods) {
            $response['ai_detection'] = $detectedFoods;
        }

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $meal = Meal::with('foodLogs')->findOrFail($id);
        
        if ($meal->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($meal);
    }

    public function update(Request $request, $id)
    {
        $meal = Meal::findOrFail($id);
        
        if ($meal->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string',
            'meal_time' => 'sometimes|date',
            'photo' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            if ($meal->photo_path) {
                Storage::disk('public')->delete($meal->photo_path);
            }
            $meal->photo_path = $request->file('photo')->store('meals', 'public');
        }

        $meal->update($request->except('photo'));

        return response()->json($meal->load('foodLogs'));
    }

    /**
     * Add food to meal
     */
    public function addFood(Request $request, $mealId)
    {
        $meal = Meal::findOrFail($mealId);
        
        if ($meal->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'food_name' => 'required|string',
            'fatsecret_food_id' => 'nullable|string',
            'brand_name' => 'nullable|string',
            'serving_size' => 'required|numeric',
            'serving_unit' => 'required|string',
            'calories' => 'required|integer',
            'protein' => 'nullable|numeric',
            'carbs' => 'nullable|numeric',
            'fat' => 'nullable|numeric',
        ]);

        $foodLog = $meal->foodLogs()->create($request->all());
        $meal->recalculateTotals();

        return response()->json($foodLog, 201);
    }

    /**
     * Remove food from meal
     */
    public function removeFood($mealId, $foodLogId)
    {
        $meal = Meal::findOrFail($mealId);
        
        if ($meal->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $foodLog = FoodLog::where('meal_id', $mealId)->findOrFail($foodLogId);
        $foodLog->delete();
        
        $meal->recalculateTotals();

        return response()->json(['message' => 'Food removed successfully']);
    }

    /**
     * Soft-delete a meal (and its food logs)
     */
    public function destroy(Request $request, $id)
    {
        $meal = Meal::where('user_id', $request->user()->id)->findOrFail($id);
        // Soft-delete all related food logs first
        $meal->foodLogs()->delete();
        // Soft-delete the meal itself
        $meal->delete();

        return response()->json(['message' => 'Meal deleted successfully'], 200);
    }

    /**
     * Restore a soft-deleted meal
     */
    public function restore(Request $request, $id)
    {
        $meal = Meal::onlyTrashed()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $meal->restore();
        $meal->foodLogs()->onlyTrashed()->restore();

        return response()->json(['message' => 'Meal restored successfully', 'meal' => $meal->load('foodLogs')]);
    }

    /**
     * Get meal history with photos
     */
    public function history(Request $request)

    {
        $query = $request->user()->meals()
            ->with('foodLogs')
            ->whereNotNull('photo_path');

        // Optional filters
        if ($request->has('limit')) {
            $limit = min($request->limit, 100); // Max 100
            $meals = $query->orderBy('meal_time', 'desc')->limit($limit)->get();
        } else {
            $meals = $query->orderBy('meal_time', 'desc')->paginate(20);
        }

        return response()->json($meals);
    }

    /**
     * Analyze photo and detect foods with AI
     */
    public function analyzePhoto($mealId)
    {
        $meal = Meal::findOrFail($mealId);
        
        if ($meal->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$meal->photo_path) {
            return response()->json(['error' => 'No photo found for this meal'], 404);
        }

        $fullPath = storage_path('app/public/' . $meal->photo_path);
        
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'Photo file not found'], 404);
        }

        $detectedFoods = $this->imageRecognitionService->analyzeImage($fullPath);

        // Optionally auto-add detected foods
        if (isset($detectedFoods['detected_foods'])) {
            foreach ($detectedFoods['detected_foods'] as $food) {
                $meal->foodLogs()->create($food);
            }
            $meal->recalculateTotals();
        }

        return response()->json([
            'meal' => $meal->load('foodLogs'),
            'ai_detection' => $detectedFoods
        ]);
    }
}
