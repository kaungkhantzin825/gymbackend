<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Register device FCM token
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'required|in:android,ios',
        ]);

        $user = $request->user();
        
        // Store FCM token in user profile or separate device_tokens table
        $user->fcm_token = $request->fcm_token;
        $user->device_type = $request->device_type;
        $user->save();

        return response()->json([
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * Send push notification to specific user
     */
    public function sendToUser($userId, $title, $body, $data = [])
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user || !$user->fcm_token) {
            return false;
        }

        return $this->sendNotification($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send push notification to multiple users
     */
    public function sendToMultipleUsers(array $userIds, $title, $body, $data = [])
    {
        $users = \App\Models\User::whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->get();

        $tokens = $users->pluck('fcm_token')->toArray();

        if (empty($tokens)) {
            return false;
        }

        return $this->sendNotificationToMultiple($tokens, $title, $body, $data);
    }

    /**
     * Send notification to topic
     */
    public function sendToTopic($topic, $title, $body, $data = [])
    {
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::error('FCM_SERVER_KEY not configured');
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', [
            'to' => '/topics/' . $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => $data,
            'priority' => 'high',
        ]);

        return $response->successful();
    }

    /**
     * Send notification to single device
     */
    private function sendNotification($token, $title, $body, $data = [])
    {
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::error('FCM_SERVER_KEY not configured');
            return false;
        }

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
            'data' => $data,
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
    }

    /**
     * Send notification to multiple devices
     */
    private function sendNotificationToMultiple(array $tokens, $title, $body, $data = [])
    {
        $serverKey = env('FCM_SERVER_KEY');

        if (!$serverKey) {
            Log::error('FCM_SERVER_KEY not configured');
            return false;
        }

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
            'data' => $data,
            'priority' => 'high',
        ]);

        return $response->successful();
    }

    /**
     * Test notification endpoint
     */
    public function sendTestNotification(Request $request)
    {
        $user = $request->user();

        if (!$user->fcm_token) {
            return response()->json([
                'error' => 'No FCM token registered for this user',
            ], 400);
        }

        $success = $this->sendNotification(
            $user->fcm_token,
            'Test Notification',
            'This is a test notification from GymApp!',
            ['type' => 'test', 'timestamp' => now()->toIso8601String()]
        );

        if ($success) {
            return response()->json([
                'message' => 'Test notification sent successfully',
            ]);
        }

        return response()->json([
            'error' => 'Failed to send notification',
        ], 500);
    }
}
