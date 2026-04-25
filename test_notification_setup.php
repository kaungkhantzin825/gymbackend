<?php

/**
 * Notification System Diagnostic Script
 * Run: php test_notification_setup.php
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "🔔 GymApp Notification System Diagnostic\n";
echo "========================================\n\n";

// Check 1: FCM Server Key
echo "1. Checking FCM_SERVER_KEY...\n";
$fcmKey = env('FCM_SERVER_KEY');
if (empty($fcmKey)) {
    echo "   ❌ FCM_SERVER_KEY is NOT set in .env\n";
    echo "   → Add FCM_SERVER_KEY to backend/.env\n";
    echo "   → Get it from: https://console.firebase.google.com/project/login-5bcfe/settings/cloudmessaging\n\n";
} else {
    echo "   ✅ FCM_SERVER_KEY is configured\n";
    echo "   → Key: " . substr($fcmKey, 0, 20) . "...\n\n";
}

// Check 2: Database Connection
echo "2. Checking database connection...\n";
try {
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Database connected\n\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check 3: Users with FCM tokens
echo "3. Checking users with FCM tokens...\n";
try {
    $users = DB::table('users')
        ->whereNotNull('fcm_token')
        ->select('id', 'name', 'email', 'device_type')
        ->get();
    
    if ($users->isEmpty()) {
        echo "   ⚠️  No users have registered FCM tokens\n";
        echo "   → Users need to open the app to register\n\n";
    } else {
        echo "   ✅ Found " . $users->count() . " user(s) with FCM tokens:\n";
        foreach ($users as $user) {
            echo "      - User #{$user->id}: {$user->name} ({$user->device_type})\n";
        }
        echo "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

// Check 4: Test FCM API
echo "4. Testing FCM API connection...\n";
if (empty($fcmKey)) {
    echo "   ⏭️  Skipped (no FCM_SERVER_KEY)\n\n";
} else {
    try {
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: key=' . $fcmKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'to' => 'test_token',
            'notification' => ['title' => 'Test', 'body' => 'Test'],
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 401) {
            echo "   ❌ Invalid FCM_SERVER_KEY\n";
            echo "   → Check your Firebase Console for the correct key\n\n";
        } elseif ($httpCode === 400) {
            echo "   ✅ FCM API is accessible (test token invalid, but API works)\n\n";
        } else {
            echo "   ✅ FCM API responded with code: $httpCode\n\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

// Summary
echo "========================================\n";
echo "📊 Summary:\n\n";

$issues = [];
if (empty($fcmKey)) {
    $issues[] = "Add FCM_SERVER_KEY to .env";
}
if (isset($users) && $users->isEmpty()) {
    $issues[] = "No users have registered FCM tokens (open app)";
}

if (empty($issues)) {
    echo "✅ All checks passed! Notification system is ready.\n";
    echo "\nNext steps:\n";
    echo "1. Test notification: php artisan notification:test 1\n";
    echo "2. Or use admin dashboard: http://127.0.0.1:8000/admin/notifications\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\nRefer to NOTIFICATION_TEST_GUIDE.md for detailed instructions.\n";
}

echo "\n";
