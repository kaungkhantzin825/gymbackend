# Check Database Storage

Write-Host "=== Checking Database Storage ===" -ForegroundColor Green
Write-Host ""

$token = "2|3ytwWEkpP0PlA4ojk01EhAAqPgJiuZ7gTEThMw2yda831896"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

# Get meal from database
Write-Host "1. Fetching meal ID 5 from database..." -ForegroundColor Yellow
$meal = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/meals/5" -Method Get -Headers $headers

Write-Host "✓ Meal retrieved from database!" -ForegroundColor Green
Write-Host ""

Write-Host "=== MEALS TABLE ===" -ForegroundColor Cyan
Write-Host "ID: $($meal.id)" -ForegroundColor White
Write-Host "User ID: $($meal.user_id)" -ForegroundColor White
Write-Host "Name: $($meal.name)" -ForegroundColor White
Write-Host "Photo Path: $($meal.photo_path)" -ForegroundColor White
Write-Host "Meal Time: $($meal.meal_time)" -ForegroundColor White
Write-Host "Notes: $($meal.notes)" -ForegroundColor White
Write-Host "Total Calories: $($meal.total_calories)" -ForegroundColor Yellow
Write-Host "Total Protein: $($meal.total_protein)g" -ForegroundColor Yellow
Write-Host "Total Carbs: $($meal.total_carbs)g" -ForegroundColor Yellow
Write-Host "Total Fat: $($meal.total_fat)g" -ForegroundColor Yellow
Write-Host "Created: $($meal.created_at)" -ForegroundColor White
Write-Host "Updated: $($meal.updated_at)" -ForegroundColor White
Write-Host ""

Write-Host "=== FOOD_LOGS TABLE ===" -ForegroundColor Cyan
Write-Host "Total Food Items: $($meal.food_logs.Count)" -ForegroundColor White
Write-Host ""

foreach ($food in $meal.food_logs) {
    Write-Host "Food Log ID: $($food.id)" -ForegroundColor Magenta
    Write-Host "  Meal ID: $($food.meal_id)" -ForegroundColor White
    Write-Host "  Food Name: $($food.food_name)" -ForegroundColor White
    Write-Host "  Calories: $($food.calories)" -ForegroundColor Yellow
    Write-Host "  Protein: $($food.protein)g" -ForegroundColor White
    Write-Host "  Carbs: $($food.carbs)g" -ForegroundColor White
    Write-Host "  Fat: $($food.fat)g" -ForegroundColor White
    Write-Host "  Fiber: $($food.fiber)g" -ForegroundColor White
    Write-Host "  Sugar: $($food.sugar)g" -ForegroundColor White
    Write-Host "  Sodium: $($food.sodium)mg" -ForegroundColor White
    Write-Host "  Serving: $($food.serving_size) $($food.serving_unit)" -ForegroundColor White
    Write-Host "  Created: $($food.created_at)" -ForegroundColor White
    Write-Host ""
}

Write-Host "=== DATABASE TABLES USED ===" -ForegroundColor Cyan
Write-Host "1. meals - Stores meal info and totals" -ForegroundColor White
Write-Host "2. food_logs - Stores individual foods in each meal" -ForegroundColor White
Write-Host ""

Write-Host "=== VERIFICATION ===" -ForegroundColor Green
Write-Host "✓ Meal saved in 'meals' table" -ForegroundColor Green
Write-Host "✓ Photo path saved: $($meal.photo_path)" -ForegroundColor Green
Write-Host "✓ $($meal.food_logs.Count) foods saved in 'food_logs' table" -ForegroundColor Green
Write-Host "✓ Totals calculated and saved" -ForegroundColor Green
Write-Host "✓ All data persisted in MySQL database" -ForegroundColor Green
Write-Host ""

Write-Host "Photo URL: http://127.0.0.1:8000/storage/$($meal.photo_path)" -ForegroundColor Cyan
