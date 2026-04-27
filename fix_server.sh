#!/bin/bash

# GymApp Server Fix Script
# Run this on your production server to fix deployment issues
# Usage: bash fix_server.sh

echo "🚀 GymApp Server Fix Script"
echo "============================"
echo ""

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo "⚠️  This script should be run as root or with sudo"
    echo "Usage: sudo bash fix_server.sh"
    exit 1
fi

# Navigate to project directory
cd /var/www/html/gymbackend || {
    echo "❌ Error: /var/www/html/gymbackend directory not found"
    exit 1
}

echo "✅ Found project directory: $(pwd)"
echo ""

# Step 1: Run diagnostic
echo "📋 Step 1: Running diagnostic..."
echo "--------------------------------"
php diagnose_server.php
echo ""

# Step 2: Check .env file
echo "📋 Step 2: Checking .env file..."
echo "--------------------------------"
if [ ! -f .env ]; then
    echo "❌ .env file not found!"
    echo "Creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate
fi

echo "Current .env configuration:"
echo "APP_URL: $(grep APP_URL .env | cut -d '=' -f2)"
echo "APP_ENV: $(grep APP_ENV .env | cut -d '=' -f2)"
echo "TWILIO_ACCOUNT_SID: $(grep TWILIO_ACCOUNT_SID .env | cut -d '=' -f2 | cut -c1-10)..."
echo "FCM_SERVER_KEY: $(grep FCM_SERVER_KEY .env | cut -d '=' -f2 | cut -c1-10)..."
echo ""

# Step 3: Clear caches
echo "📋 Step 3: Clearing Laravel caches..."
echo "--------------------------------"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

# Step 4: Cache config and routes
echo "📋 Step 4: Caching config and routes..."
echo "--------------------------------"
php artisan config:cache
php artisan route:cache
echo "✅ Config and routes cached"
echo ""

# Step 5: Fix permissions
echo "📋 Step 5: Fixing file permissions..."
echo "--------------------------------"
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
echo "✅ Permissions fixed"
echo ""

# Step 6: Enable Apache modules
echo "📋 Step 6: Enabling Apache modules..."
echo "--------------------------------"
a2enmod rewrite
a2enmod headers
echo "✅ Apache modules enabled"
echo ""

# Step 7: Restart Apache
echo "📋 Step 7: Restarting Apache..."
echo "--------------------------------"
systemctl restart apache2
echo "✅ Apache restarted"
echo ""

# Step 8: Test API
echo "📋 Step 8: Testing API endpoints..."
echo "--------------------------------"

echo "Test 1: GET /api/videos"
curl -s -o /dev/null -w "Status: %{http_code}\n" https://gym.pnpmyanmar.com/api/videos
echo ""

echo "Test 2: POST /api/login (local)"
curl -s -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user3@gym.com","password":"12345678"}' \
  -w "\nStatus: %{http_code}\n"
echo ""

echo "Test 3: POST /api/login (external)"
curl -s -X POST https://gym.pnpmyanmar.com/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user3@gym.com","password":"12345678"}' \
  -w "\nStatus: %{http_code}\n"
echo ""

# Final summary
echo "================================"
echo "✅ Server fix script completed!"
echo "================================"
echo ""
echo "📋 Next Steps:"
echo "1. Check if all tests passed above"
echo "2. If .env variables are missing, edit .env file:"
echo "   nano /var/www/html/gymbackend/.env"
echo "3. Add missing Twilio and FCM credentials"
echo "4. Run this script again: bash fix_server.sh"
echo ""
echo "📞 If you still have issues:"
echo "- Check Laravel logs: tail -f storage/logs/laravel.log"
echo "- Check Apache logs: tail -f /var/log/apache2/gym_error.log"
echo "- Run diagnostic: php diagnose_server.php"
echo ""
