<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeightLog;
use Illuminate\Http\Request;

class WeightLogController extends Controller
{
    public function index(Request $request)
    {
        $weightLogs = $request->user()->weightLogs()
            ->orderBy('logged_at', 'desc')
            ->paginate(50);

        return response()->json($weightLogs);
    }

    public function store(Request $request)
    {
        $request->validate([
            'weight' => 'required|numeric|min:1|max:500',
            'logged_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $weightLog = $request->user()->weightLogs()->create($request->all());

        // Update user profile current weight
        $profile = $request->user()->profile;
        if ($profile) {
            $profile->update(['current_weight' => $request->weight]);
        }

        return response()->json($weightLog, 201);
    }

    public function show($id)
    {
        $weightLog = WeightLog::findOrFail($id);
        
        if ($weightLog->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($weightLog);
    }

    public function update(Request $request, $id)
    {
        $weightLog = WeightLog::findOrFail($id);
        
        if ($weightLog->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'weight' => 'sometimes|numeric|min:1|max:500',
            'logged_at' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $weightLog->update($request->all());

        return response()->json($weightLog);
    }

    public function destroy($id)
    {
        $weightLog = WeightLog::findOrFail($id);
        
        if ($weightLog->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $weightLog->delete();

        return response()->json(['message' => 'Weight log deleted successfully']);
    }
}
