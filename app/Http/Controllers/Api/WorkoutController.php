<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Carbon\Carbon;

class WorkoutController extends Controller
{
    public function index(Request $request)
    {
        $workouts = $request->user()->workouts()->with('sets.exercise')->orderBy('created_at', 'desc')->paginate(10);
        return response()->json($workouts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'started_at' => 'required|date',
        ]);

        $workout = $request->user()->workouts()->create([
            'name' => $request->name,
            'started_at' => Carbon::parse($request->started_at),
        ]);

        return response()->json($workout, 201);
    }

    public function show(Request $request, Workout $workout)
    {
        if ($workout->user_id !== $request->user()->id) abort(403);
        $workout->load('sets.exercise');
        return response()->json($workout);
    }

    public function addSet(Request $request, Workout $workout)
    {
        if ($workout->user_id !== $request->user()->id) abort(403);

        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'set_number' => 'required|integer',
            'reps' => 'required|integer',
            'weight' => 'required|numeric',
            'rpe' => 'nullable|integer|min:1|max:10',
            'rest_time_seconds' => 'nullable|integer',
            'is_superset' => 'boolean'
        ]);

        $set = $workout->sets()->create($request->all());

        // Update running volume
        $workout->increment('total_volume', $request->reps * $request->weight);

        return response()->json($set->load('exercise'), 201);
    }

    public function completeWorkout(Request $request, Workout $workout)
    {
        if ($workout->user_id !== $request->user()->id) abort(403);

        $request->validate([
            'ended_at' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $endedAt = Carbon::parse($request->ended_at);
        $duration = $endedAt->diffInMinutes($workout->started_at);

        $workout->update([
            'ended_at' => $endedAt,
            'notes' => $request->notes,
            'total_duration_minutes' => abs($duration)
        ]);

        return response()->json($workout->load('sets.exercise'));
    }

    public function destroy(Request $request, Workout $workout)
    {
        if ($workout->user_id !== $request->user()->id) abort(403);
        $workout->delete();
        return response()->json(['message' => 'Workout deleted']);
    }
}
