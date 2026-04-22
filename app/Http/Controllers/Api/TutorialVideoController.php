<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TutorialVideo;
use Illuminate\Http\Request;

class TutorialVideoController extends Controller
{
    public function index(Request $request)
    {
        $query = TutorialVideo::query()->where('is_active', true);

        // Filter by category (Strength, Cardio, HIIT, Flexibility)
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by gender (male / female / both)
        if ($request->has('gender')) {
            $query->where(function ($q) use ($request) {
                $q->where('gender', $request->gender)
                  ->orWhereNull('gender');
            });
        }

        $videos = $query->orderBy('created_at', 'desc')->get();

        return response()->json($videos);
    }

    public function show($id)
    {
        return response()->json(TutorialVideo::findOrFail($id));
    }
}
