<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Meal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function index()
    {
        $totalUsers = User::count();
        $activeDevices = User::whereNotNull('fcm_token')->count();
        $totalMeals = Meal::count();
        $users = User::orderBy('created_at', 'desc')->get();

        return view('admin.notifications', compact('totalUsers', 'activeDevices', 'totalMeals', 'users'));
    }

    /**
     * Send push notification
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'recipient' => 'required|in:all,specific',
            'user_id' => 'required_if:recipient,specific|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            return response()->json([
                'error' => 'FCM_SERVER_KEY not configured in .env file'
            ], 500);
        }

        $title = $request->title;
        $body = $request->body;

        if ($request->recipient === 'all') {
            // Send to all users with FCM tokens
            $users = User::whereNotNull('fcm_token')->get();
            
            if ($users->isEmpty()) {
                return response()->json([
                    'error' => 'No users with active devices found'
                ], 400);
            }

            $tokens = $users->pluck('fcm_token')->toArray();
            $success = $this->sendToMultipleDevices($tokens, $title, $body, $serverKey);

            if ($success) {
                return response()->json([
                    'message' => "Notification sent to {$users->count()} users successfully!"
                ]);
            }
        } else {
            // Send to specific user
            $user = User::find($request->user_id);

            if (!$user->fcm_token) {
                return response()->json([
                    'error' => 'User does not have an active device'
                ], 400);
            }

            $success = $this->sendToDevice($user->fcm_token, $title, $body, $serverKey);

            if ($success) {
                return response()->json([
                    'message' => "Notification sent to {$user->name} successfully!"
                ]);
            }
        }

        return response()->json([
            'error' => 'Failed to send notification'
        ], 500);
    }

    /**
     * Send notification to single device
     */
    private function sendToDevice($token, $title, $body, $serverKey)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => [
                    'type' => 'admin_notification',
                    'timestamp' => now()->toIso8601String(),
                ],
                'priority' => 'high',
            ]);

            if ($response->failed()) {
                Log::error('FCM notification failed', [
                    'token' => $token,
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple devices
     */
    private function sendToMultipleDevices(array $tokens, $title, $body, $serverKey)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => [
                    'type' => 'admin_notification',
                    'timestamp' => now()->toIso8601String(),
                ],
                'priority' => 'high',
            ]);

            if ($response->failed()) {
                Log::error('FCM bulk notification failed', [
                    'response' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('FCM bulk notification exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
