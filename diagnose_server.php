<?php
/**
 * Server Diagnostic Script
 * Run this on your production server to diagnose issues
 * 
 * Usage: php diagnose_server.php
 */

echo "🔍 GymApp Server Diagnostic Tool\n";
echo "================================\n\n";

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "✅ Laravel loaded successfully\n\n";

// Check Environment
echo "📋 Environment Configuration:\n";
echo "----------------------------\n";
echo "APP_ENV: " . env('APP_ENV') . "\n";
echo "APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "\n";
echo "APP_URL: " . env('APP_URL') . "\n";
echo "APP_KEY: " . (env('APP_KEY') ? '✅ Set' : '❌ Missing') . "\n\n";

// Check Database
echo "🗄️  Database Configuration:\n";
echo "----------------------------\n";
echo "DB_CONNECTION: " . env('DB_CONNECTION') . "\n";
echo "DB_HOST: " . env('DB_HOST') . "\n";
echo "DB_PORT: " . env('DB_PORT') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME') . "\n";

try {
    DB::connection()->getPdo();
    echo "✅ Database connection: SUCCESS\n";
    
    // Count users
    $userCount = DB::table('users')->count();
    echo "👥 Total users: {$userCount}\n";
    
    // Check test user
    $testUser = DB::table('users')->where('email', 'user3@gym.com')->first();
    if ($testUser) {
        echo "✅ Test user (user3@gym.com) exists\n";
    } else {
        echo "⚠️  Test user (user3@gym.com) not found\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Check Twilio Configuration
echo "📱 Twilio Configuration:\n";
echo "----------------------------\n";
$twilioSid = env('TWILIO_ACCOUNT_SID');
$twilioToken = env('TWILIO_AUTH_TOKEN');
$twilioPhone = env('TWILIO_PHONE_NUMBER');

echo "TWILIO_ACCOUNT_SID: " . ($twilioSid ? '✅ Set (' . substr($twilioSid, 0, 10) . '...)' : '❌ Missing') . "\n";
echo "TWILIO_AUTH_TOKEN: " . ($twilioToken ? '✅ Set (' . substr($twilioToken, 0, 10) . '...)' : '❌ Missing') . "\n";
echo "TWILIO_PHONE_NUMBER: " . ($twilioPhone ? '✅ Set (' . $twilioPhone . ')' : '❌ Missing') . "\n";

if (!$twilioSid || !$twilioToken || !$twilioPhone) {
    echo "⚠️  WARNING: Twilio not configured - Phone login will NOT work\n";
}
echo "\n";

// Check Firebase Configuration
echo "🔔 Firebase Configuration:\n";
echo "----------------------------\n";
$fcmKey = env('FCM_SERVER_KEY');
$firebaseProjectId = env('FIREBASE_PROJECT_ID');
$firebaseApiKey = env('FIREBASE_API_KEY');

echo "FCM_SERVER_KEY: " . ($fcmKey ? '✅ Set (' . substr($fcmKey, 0, 10) . '...)' : '❌ Missing') . "\n";
echo "FIREBASE_PROJECT_ID: " . ($firebaseProjectId ? '✅ Set (' . $firebaseProjectId . ')' : '❌ Missing') . "\n";
echo "FIREBASE_API_KEY: " . ($firebaseApiKey ? '✅ Set (' . substr($firebaseApiKey, 0, 10) . '...)' : '❌ Missing') . "\n";

if (!$fcmKey) {
    echo "⚠️  WARNING: FCM_SERVER_KEY not configured - Push notifications will NOT work\n";
}
echo "\n";

// Check API Keys
echo "🔑 API Keys:\n";
echo "----------------------------\n";
echo "GEMINI_API_KEY: " . (env('GEMINI_API_KEY') ? '✅ Set' : '❌ Missing') . "\n";
echo "FATSECRET_CLIENT_ID: " . (env('FATSECRET_CLIENT_ID') ? '✅ Set' : '❌ Missing') . "\n";
echo "GOOGLE_VISION_API_KEY: " . (env('GOOGLE_VISION_API_KEY') ? '✅ Set' : '❌ Missing') . "\n";
echo "CLARIFAI_API_KEY: " . (env('CLARIFAI_API_KEY') ? '✅ Set' : '❌ Missing') . "\n";
echo "\n";

// Check File Permissions
echo "📁 File Permissions:\n";
echo "----------------------------\n";
$storagePath = storage_path();
$bootstrapCachePath = base_path('bootstrap/cache');

echo "Storage directory: " . $storagePath . "\n";
echo "Storage writable: " . (is_writable($storagePath) ? '✅ Yes' : '❌ No') . "\n";
echo "Bootstrap cache writable: " . (is_writable($bootstrapCachePath) ? '✅ Yes' : '❌ No') . "\n";
echo "\n";

// Check Routes
echo "🛣️  API Routes:\n";
echo "----------------------------\n";
$routes = Route::getRoutes();
$apiRoutes = 0;
foreach ($routes as $route) {
    if (str_starts_with($route->uri(), 'api/')) {
        $apiRoutes++;
    }
}
echo "Total API routes: {$apiRoutes}\n";
echo "\n";

// Test Login Endpoint
echo "🧪 Testing Login Endpoint:\n";
echo "----------------------------\n";
try {
    $response = app()->handle(
        Illuminate\Http\Request::create('/api/login', 'POST', [
            'email' => 'user3@gym.com',
            'password' => '12345678'
        ], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ])
    );
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response: " . substr($response->getContent(), 0, 200) . "...\n";
    
    if ($response->getStatusCode() === 200) {
        echo "✅ Login endpoint working!\n";
    } else {
        echo "⚠️  Login returned non-200 status\n";
    }
} catch (Exception $e) {
    echo "❌ Login test failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Summary
echo "📊 Summary:\n";
echo "================================\n";
$issues = [];

if (!env('APP_KEY')) $issues[] = "APP_KEY missing";
if (!$twilioSid || !$twilioToken || !$twilioPhone) $issues[] = "Twilio not configured";
if (!$fcmKey) $issues[] = "FCM_SERVER_KEY missing";
if (!is_writable($storagePath)) $issues[] = "Storage not writable";
if (!is_writable($bootstrapCachePath)) $issues[] = "Bootstrap cache not writable";

if (empty($issues)) {
    echo "✅ All critical checks passed!\n";
    echo "Your server should be working correctly.\n";
} else {
    echo "⚠️  Found " . count($issues) . " issue(s):\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
    echo "\nPlease fix these issues and run this script again.\n";
}

echo "\n";
echo "🔧 Next Steps:\n";
echo "1. Fix any issues listed above\n";
echo "2. Run: php artisan config:clear && php artisan cache:clear\n";
echo "3. Run: php artisan config:cache && php artisan route:cache\n";
echo "4. Test API: curl -X POST http://localhost/api/login -H 'Content-Type: application/json' -d '{\"email\":\"user3@gym.com\",\"password\":\"12345678\"}'\n";
echo "\n";
