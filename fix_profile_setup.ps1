# PowerShell script to fix profile update setup
# Run this from the backend directory

Write-Host "=== Profile Update Setup Fix ===" -ForegroundColor Green
Write-Host ""

# Step 1: Run migrations
Write-Host "Step 1: Running migrations..." -ForegroundColor Yellow
php artisan migrate --force
if ($LASTEXITCODE -eq 0) {
    Write-Host "โœ… Migrations completed" -ForegroundColor Green
} else {
    Write-Host "โŒ Migration failed" -ForegroundColor Red
}
Write-Host ""

# Step 2: Create storage link
Write-Host "Step 2: Creating storage link..." -ForegroundColor Yellow
php artisan storage:link
if ($LASTEXITCODE -eq 0) {
    Write-Host "โœ… Storage link created" -ForegroundColor Green
} else {
    Write-Host "โš ๏ธ Storage link may already exist" -ForegroundColor Yellow
}
Write-Host ""

# Step 3: Create profile_photos directory
Write-Host "Step 3: Creating profile_photos directory..." -ForegroundColor Yellow
$profilePhotosPath = "storage\app\public\profile_photos"
if (!(Test-Path $profilePhotosPath)) {
    New-Item -ItemType Directory -Path $profilePhotosPath -Force | Out-Null
    Write-Host "โœ… Created profile_photos directory" -ForegroundColor Green
} else {
    Write-Host "โœ… profile_photos directory already exists" -ForegroundColor Green
}
Write-Host ""

# Step 4: Check routes
Write-Host "Step 4: Checking routes..." -ForegroundColor Yellow
$routes = php artisan route:list --json | ConvertFrom-Json
$profileRoutes = $routes | Where-Object { $_.uri -like "*profile*" }
if ($profileRoutes.Count -gt 0) {
    Write-Host "โœ… Profile routes found:" -ForegroundColor Green
    foreach ($route in $profileRoutes) {
        Write-Host "   $($route.method) $($route.uri)" -ForegroundColor Cyan
    }
} else {
    Write-Host "โŒ No profile routes found" -ForegroundColor Red
}
Write-Host ""

# Step 5: Clear cache
Write-Host "Step 5: Clearing cache..." -ForegroundColor Yellow
php artisan config:clear | Out-Null
php artisan route:clear | Out-Null
php artisan cache:clear | Out-Null
Write-Host "โœ… Cache cleared" -ForegroundColor Green
Write-Host ""

Write-Host "=== Setup Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Restart Laravel server: php artisan serve" -ForegroundColor Cyan
Write-Host "2. Restart Flutter app: flutter run" -ForegroundColor Cyan
Write-Host "3. Test profile update in app" -ForegroundColor Cyan
Write-Host ""
Write-Host "If issues persist, check:" -ForegroundColor Yellow
Write-Host "- Laravel logs: storage\logs\laravel.log" -ForegroundColor Cyan
Write-Host "- Flutter console for errors" -ForegroundColor Cyan
Write-Host "- Database connection" -ForegroundColor Cyan
