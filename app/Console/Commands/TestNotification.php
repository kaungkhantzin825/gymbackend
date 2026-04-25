<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestNotification extends Command
{
    protected $signature = 'notification:test {user_id?}';
    protected $description = 'Test push notification to a user';

    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User #{$userId} not found");
            return 1;
        }

        $this->info("Testing notification for: {$user->name} ({$user->email})");
        
        if (!$user->fcm_token) {
            $this->error("No FCM token registered for this user");
            $this->info("User needs to open the app to register FCM token");
            return 1;
        }

        $this->info("FCM Token: " . substr($user->fcm_token, 0, 20) . "...");
        $this->info("Device Type: " . ($user->device_type ?? 'unknown'));
        
        $serverKey = env('FCM_SERVER_KEY');
        
        if (!$serverKey) {
            $this->error("FCM_SERVER_KEY not configured in .env");
            $this->info("Please add FCM_SERVER_KEY to your .env file");
            return 1;
        }

        $this->info("FCM Server Key: " . substr($serverKey, 0, 20) . "...");
        $this->info("Sending notification...");

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $user->fcm_token,
                'notification' => [
                    'title' => 'Test Notification',
                    'body' => 'This is a test notification from GymApp CLI!',
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => [
                    'type' => 'test',
                    'timestamp' => now()->toIso8601String(),
                ],
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                $this->info("✅ Notification sent successfully!");
                $this->info("Response: " . $response->body());
                return 0;
            } else {
                $this->error("❌ Failed to send notification");
                $this->error("Status: " . $response->status());
                $this->error("Response: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            return 1;
        }
    }
}
