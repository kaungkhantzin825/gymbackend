<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FitAI Pro — Food Image Recognition Service
 *
 * Uses Gemini Vision (gemini-2.5-flash) as the primary AI engine.
 * Falls back to a smart generic estimator when the API is unavailable.
 *
 * Flow: Gemini → Generic Estimator
 */
class FoodImageRecognitionService
{
    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Analyze a food image and return detected food items with nutrition data.
     * The user profile is optionally injected for personalized advice.
     *
     * @param  string     $imagePath   Absolute path to the uploaded image
     * @param  array|null $userProfile Optional user profile for tailored advice
     * @return array
     */
    public function analyzeImage(string $imagePath, ?array $userProfile = null): array
    {
        if (!file_exists($imagePath)) {
            Log::warning('FoodImageRecognitionService: image not found at ' . $imagePath);
            return $this->estimateGenericMeal();
        }

        // Primary: Gemini Vision (gemini-2.5-flash — confirmed working)
        $geminiResult = $this->analyzeWithGemini($imagePath, $userProfile);
        if (!empty($geminiResult['detected_foods'])) {
            Log::info('FitAI: Gemini detected ' . count($geminiResult['detected_foods']) . ' food item(s)');
            return $geminiResult;
        }

        // Final fallback: show the user something rather than nothing
        Log::warning('FitAI: All AI engines failed — returning generic estimator');
        return $this->estimateGenericMeal();
    }

    // ─── Gemini Vision ────────────────────────────────────────────────────────

    private function analyzeWithGemini(string $imagePath, ?array $userProfile): array
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            Log::warning('FitAI Gemini: GEMINI_API_KEY not set');
            return ['detected_foods' => []];
        }

        try {
            $imageContent = base64_encode(file_get_contents($imagePath));
            $mimeType     = mime_content_type($imagePath) ?: 'image/jpeg';

            $prompt = $this->buildFitAIPrompt($userProfile);

            $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageContent]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'topP'            => 0.8,
                    'topK'            => 20,
                    'maxOutputTokens' => 4096,
                ],
            ];

            // Try models in order — gemini-2.5-flash confirmed working
            $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];

            foreach ($models as $model) {
                $url      = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(45)->post($url, $payload);

                if ($response->successful()) {
                    Log::info("FitAI Gemini: model {$model} responded successfully");
                    $result = $this->parseGeminiResponse($response->json());

                    if (!empty($result['detected_foods'])) {
                        return array_merge($result, ['ai_model' => $model, 'ai_engine' => 'gemini']);
                    }

                    Log::warning("FitAI Gemini: {$model} returned no food items");
                    continue;
                }

                $status = $response->status();
                $errMsg = $response->json()['error']['message'] ?? 'unknown';
                Log::warning("FitAI Gemini: {$model} HTTP {$status}: " . substr($errMsg, 0, 200));

                // Retry on quota/rate-limit or model-not-found errors; break on others
                if (!in_array($status, [429, 404, 503])) {
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::error('FitAI Gemini Exception: ' . $e->getMessage());
        }

        return ['detected_foods' => []];
    }

    // ─── FitAI Pro System Prompt ──────────────────────────────────────────────

    private function buildFitAIPrompt(?array $profile): string
    {
        $goal      = $profile['fitness_goal']    ?? 'Stay Healthy';
        $weight    = $profile['weight']          ?? 'unknown';
        $ageVal    = $profile['age']             ?? 'unknown';
        $gender    = $profile['gender']          ?? 'unknown';

        return <<<PROMPT
You are FitAI Pro, an expert nutritionist embedded in a gym tracking app.

USER PROFILE:
- Age: {$ageVal}
- Gender: {$gender}
- Weight: {$weight} kg
- Fitness Goal: {$goal}

TASK — FOOD PHOTO ANALYSIS:
Analyze the food image carefully. Identify EVERY food item visible, including sides, sauces, garnishes.
Estimate realistic portion sizes (not too low or too high).

Return ONLY a raw JSON array (no markdown, no explanation, no code blocks):
[
  {
    "food_name": "Specific Food Name",
    "serving_size": 150,
    "serving_unit": "g",
    "calories": 250,
    "protein": 30.0,
    "carbs": 15.0,
    "fat": 8.0,
    "fiber": 2.0,
    "sugar": 1.0,
    "sodium": 400,
    "health_score": 7
  }
]

RULES:
1. List each food item separately (rice, protein, vegetable, sauce = 4 items)
2. For Asian meals: detect rice, main protein, egg, vegetables, sambal/sauce, etc.
3. Estimate calories ACCURATELY based on portion sizes
4. All numbers must be numeric (not strings)
5. If no food is visible, return: []

PORTION REFERENCE:
- Rice (1 cup, 200g): 260 kcal, 5g protein, 56g carbs, 0.6g fat
- Fried chicken piece (120g): 348 kcal, 30g protein, 0g carbs, 21.6g fat
- Hard boiled egg (50g): 70 kcal, 6g protein, 0.5g carbs, 5g fat
- Stir-fried vegetables (80g): 50 kcal, 2g protein, 6g carbs, 2g fat
- Sambal/chili sauce (20g): 30 kcal, 0.5g protein, 3g carbs, 2g fat

Return ONLY the JSON array, nothing else.
PROMPT;
    }

    // ─── Response Parser ──────────────────────────────────────────────────────

    private function parseGeminiResponse(array $geminiData): array
    {
        $raw = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        Log::info('FitAI Gemini raw response (first 500 chars): ' . substr($raw, 0, 500));

        // Strip markdown code fences if present
        $text = preg_replace('/```[a-z]*\s*/i', '', $raw);
        $text = str_replace('```', '', $text);
        $text = trim($text);

        $items = [];

        // Attempt 1: extract a JSON array  [...]
        if (preg_match('/\[[\s\S]*\]/s', $text, $m)) {
            $arr = json_decode($m[0], true);
            if (is_array($arr) && !empty($arr)) {
                $items = $arr;
                Log::info('FitAI: parsed ' . count($items) . ' items from JSON array');
            }
        }

        // Attempt 2: extract a JSON object  { "foods": [...] } or { "detected_foods": [...] }
        if (empty($items) && preg_match('/\{[\s\S]*\}/s', $text, $m)) {
            $obj = json_decode($m[0], true);
            if (is_array($obj)) {
                $items = $obj['foods']
                      ?? $obj['detected_foods']
                      ?? $obj['items']
                      ?? (isset($obj['food_name']) ? [$obj] : []);
                if (!empty($items)) {
                    Log::info('FitAI: parsed ' . count($items) . ' items from JSON object');
                }
            }
        }

        if (empty($items)) {
            Log::warning('FitAI: could not parse any food items from Gemini response');
            return ['detected_foods' => []];
        }

        $foods = [];
        foreach ($items as $food) {
            if (!is_array($food)) continue;

            $name = trim($food['food_name'] ?? $food['foodName'] ?? $food['name'] ?? '');
            if (empty($name)) continue;

            $calories    = (int) round((float)($food['calories'] ?? 0));
            $servingSize = (float)($food['serving_size'] ?? $food['servingSize'] ?? 100);

            if ($calories <= 0) continue; // skip placeholder items

            Log::info("FitAI item: {$name} | {$servingSize}g | {$calories} kcal");

            $foods[] = [
                'food_name'         => $name,
                'fatsecret_food_id' => 'gemini_ai',
                'brand_name'        => 'AI Detected',
                'serving_size'      => $servingSize,
                'serving_unit'      => $food['serving_unit'] ?? $food['servingUnit'] ?? 'g',
                'calories'          => $calories,
                'protein'           => (float) round($food['protein']  ?? 0, 1),
                'carbs'             => (float) round($food['carbs']    ?? $food['carbohydrates'] ?? 0, 1),
                'fat'               => (float) round($food['fat']      ?? 0, 1),
                'fiber'             => (float) round($food['fiber']    ?? 0, 1),
                'sugar'             => (float) round($food['sugar']    ?? 0, 1),
                'sodium'            => (float) round($food['sodium']   ?? 0, 1),
                'health_score'      => (int)($food['health_score'] ?? $food['healthScore'] ?? 5),
                'confidence'        => 90,
            ];
        }

        if (empty($foods)) {
            Log::warning('FitAI: items were present but none passed validation (all had 0 calories?)');
            return ['detected_foods' => []];
        }

        $totalKcal = array_sum(array_column($foods, 'calories'));
        Log::info('FitAI: ' . count($foods) . ' foods detected, total ' . $totalKcal . ' kcal');

        return [
            'detected_foods' => $foods,
            'total_items'    => count($foods),
            'total_calories' => $totalKcal,
            'ai_status'      => 'gemini_success',
        ];
    }

    // ─── Generic Fallback Estimator ───────────────────────────────────────────

    private function estimateGenericMeal(): array
    {
        return [
            'detected_foods' => [
                [
                    'food_name'         => 'Mixed Meal (Estimated)',
                    'fatsecret_food_id' => 'estimated',
                    'brand_name'        => 'Calorie Estimator',
                    'serving_size'      => 300,
                    'serving_unit'      => 'g',
                    'calories'          => 450,
                    'protein'           => 25.0,
                    'carbs'             => 50.0,
                    'fat'               => 15.0,
                    'fiber'             => 5.0,
                    'sugar'             => 8.0,
                    'sodium'            => 600.0,
                    'health_score'      => 5,
                    'confidence'        => 50,
                ],
            ],
            'total_items'   => 1,
            'ai_status'     => 'estimated',
            'hint'          => 'AI unavailable — showing estimated values. Edit the meal to correct them.',
        ];
    }
}
