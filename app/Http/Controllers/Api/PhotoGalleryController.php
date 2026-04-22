<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoGalleryController extends Controller
{
    public function index(Request $request)
    {
        $photos = $request->user()->photoGalleries()->orderBy('taken_at', 'desc')->get();
        return response()->json($photos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:5120',
            'caption' => 'nullable|string',
            'taken_at' => 'required|date'
        ]);

        $photoUrl = $request->file('photo')->store('gallery', 'public');

        $photo = $request->user()->photoGalleries()->create([
            'photo_url' => asset('storage/' . $photoUrl),
            'caption' => $request->caption,
            'taken_at' => $request->taken_at
        ]);

        return response()->json($photo, 201);
    }

    public function destroy($id, Request $request)
    {
        $photo = $request->user()->photoGalleries()->findOrFail($id);
        
        $path = str_replace(asset('storage/'), '', $photo->photo_url);
        Storage::disk('public')->delete($path);
        
        $photo->delete();
        
        return response()->json(['message' => 'Deleted']);
    }
}
