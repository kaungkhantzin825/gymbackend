<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\TutorialVideo;
use App\Models\SupportMessage;
use App\Models\AppSetting;

class AdminController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::guard('web')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_videos' => TutorialVideo::count(),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }

    public function videos()
    {
        $videos = TutorialVideo::all();
        return view('admin.videos', compact('videos'));
    }

    public function users()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    public function support()
    {
        $messages = SupportMessage::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.support', compact('messages'));
    }

    public function getSupportMessage($id)
    {
        $message = SupportMessage::with('user')->findOrFail($id);
        
        // Mark as read
        if ($message->status === 'pending') {
            $message->update(['status' => 'read']);
        }
        
        return response()->json($message);
    }

    public function replySupportMessage(Request $request, $id)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $message = SupportMessage::findOrFail($id);
        $message->update([
            'admin_reply' => $request->reply,
            'status' => 'replied',
            'replied_at' => now(),
        ]);

        return response()->json(['message' => 'Reply sent successfully']);
    }

    public function settings()
    {
        $about = AppSetting::get('about', '');
        $privacy = AppSetting::get('privacy_policy', '');
        return view('admin.settings', compact('about', 'privacy'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'key' => 'required|in:about,privacy_policy',
            'value' => 'required|string',
        ]);

        AppSetting::set($request->key, $request->value);

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }
}
