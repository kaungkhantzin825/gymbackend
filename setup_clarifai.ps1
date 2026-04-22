# Clarifai Setup Script
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Clarifai Free Setup - No Credit Card!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Step 1: Sign Up (FREE)" -ForegroundColor Yellow
Write-Host "  Visit: https://clarifai.com/signup" -ForegroundColor White
Write-Host "  - No credit card required" -ForegroundColor Green
Write-Host "  - 1,000 operations/month free" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Get API Key" -ForegroundColor Yellow
Write-Host "  After signup, go to:" -ForegroundColor White
Write-Host "  https://clarifai.com/settings/security" -ForegroundColor White
Write-Host "  - Create Personal Access Token" -ForegroundColor Green
Write-Host "  - Copy the API key" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Add to .env" -ForegroundColor Yellow
Write-Host "  Open: backend/.env" -ForegroundColor White
Write-Host "  Find: CLARIFAI_API_KEY=" -ForegroundColor White
Write-Host "  Paste your key after the =" -ForegroundColor Green
Write-Host ""

Write-Host "Step 4: Clear Cache" -ForegroundColor Yellow
Write-Host "  Run: php artisan config:clear" -ForegroundColor White
Write-Host ""

Write-Host "Step 5: Test!" -ForegroundColor Yellow
Write-Host "  Upload a meal photo in your app" -ForegroundColor White
Write-Host "  AI will detect foods automatically" -ForegroundColor Green
Write-Host ""

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Benefits:" -ForegroundColor Yellow
Write-Host "  - FREE (1,000/month)" -ForegroundColor Green
Write-Host "  - No credit card" -ForegroundColor Green
Write-Host "  - 500+ food types" -ForegroundColor Green
Write-Host "  - Easy setup" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$response = Read-Host "Have you signed up and got your API key? (y/n)"

if ($response -eq "y" -or $response -eq "Y") {
    Write-Host ""
    $apiKey = Read-Host "Paste your Clarifai API key here"
    
    if ($apiKey) {
        # Update .env file
        $envContent = Get-Content .env -Raw
        $envContent = $envContent -replace 'CLARIFAI_API_KEY=.*', "CLARIFAI_API_KEY=$apiKey"
        Set-Content .env -Value $envContent
        
        Write-Host ""
        Write-Host "API key saved to .env!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Clearing Laravel cache..." -ForegroundColor Yellow
        php artisan config:clear
        Write-Host ""
        Write-Host "Setup complete! Upload a meal photo to test." -ForegroundColor Green
    } else {
        Write-Host "No API key provided. Please run this script again." -ForegroundColor Red
    }
} else {
    Write-Host ""
    Write-Host "No problem! Follow the steps above when ready." -ForegroundColor Cyan
    Write-Host "Run this script again after you get your API key." -ForegroundColor Cyan
}

Write-Host ""
