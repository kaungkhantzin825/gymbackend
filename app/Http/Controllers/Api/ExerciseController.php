<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->exercises();

        if ($request->has('date')) {
            $query->whereDate('exercise_time', $request->date);
        }

        if ($request->has('type')) {
            $query->where('exercise_type', $request->type);
        }

        $exercises = $query->orderBy('exercise_time', 'desc')->paginate(20);

        return response()->json($exercises);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exercise_name' => 'required|string',
            'exercise_type' => 'required|in:cardio,strength,flexibility,sports',
            'exercise_time' => 'required|date',
            'duration_minutes' => 'nullable|integer',
            'calories_burned' => 'required|integer|min:0',
            'sets' => 'nullable|integer',
            'reps' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $exercise = $request->user()->exercises()->create($request->all());

        return response()->json($exercise, 201);
    }

    public function show($id)
    {
        $exercise = Exercise::findOrFail($id);
        
        if ($exercise->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($exercise);
    }

    public function update(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);
        
        if ($exercise->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'exercise_name' => 'sometimes|string',
            'exercise_type' => 'sometimes|in:cardio,strength,flexibility,sports',
            'exercise_time' => 'sometimes|date',
            'duration_minutes' => 'nullable|integer',
            'calories_burned' => 'sometimes|integer|min:0',
            'sets' => 'nullable|integer',
            'reps' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        $exercise->update($request->all());

        return response()->json($exercise);
    }

    public function destroy($id)
    {
        $exercise = Exercise::findOrFail($id);
        
        if ($exercise->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $exercise->delete();

        return response()->json(['message' => 'Exercise deleted successfully']);
    }
}
