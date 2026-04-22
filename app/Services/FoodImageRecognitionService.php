<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FoodImageRecognitionService
{
    private $fatSecretService;

    public function __construct(FatSecretService $fatSecretService)
    {
        $this->fatSecretService = $fatSecretService;
    }

    /**
     * Main entry — Clarifai (new account) → Gemini (new key) → Google Vision → Estimator fallback
     */
    public function analyzeImage($imagePath)
    {
        // Try Clarifai first (new account with fresh credits)
        $clarifaiResult = $this->analyzeWithClarifai($imagePath);
        if (!empty($clarifaiResult['detected_foods'])) {
            return $clarifaiResult;
        }

        // Try Gemini second (new API key with fresh quota)
        $geminiResult = $this->analyzeWithGemini($imagePath);
        if (!empty($geminiResult['detected_foods'])) {
            return $geminiResult;
        }

        // Try Google Vision third (requires billing but you have key)
        $googleResult = $this->analyzeWithGoogleVision($imagePath);
        if (!empty($googleResult['detected_foods'])) {
            return $googleResult;
        }

        // Final fallback — generic estimator so user always sees something
        return $this->estimateGenericMeal();
    }

    /**
     * Clarifai Food Model — identifies food from image labels
     * then looks up nutrition via FatSecret
     */
    private function analyzeWithClarifai($imagePath): array
    {
        $apiKey = env('CLARIFAI_API_KEY');
        $userId = env('CLARIFAI_USER_ID', 'l0pdwy3tbda4');
        $appId = env('CLARIFAI_APP_ID', 'food-recognition');
        
        if (!$apiKey || !file_exists($imagePath)) {
            return ['detected_foods' => []];
        }

        try {
            $imageContent = base64_encode(file_get_contents($imagePath));

            $response = Http::withHeaders([
                'Authorization' => "Key {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(20)->post(
                // Use Clarifai's public food model in the main app
                'https://api.clarifai.com/v2/users/clarifai/apps/main/models/food-item-recognition/outputs',
                [
                    // Specify your user app for billing/quota tracking
                    'user_app_id' => [
                        'user_id' => $userId,
                        'app_id'  => $appId,
                    ],
                    'inputs' => [[
                        'data' => ['image' => ['base64' => $imageContent]],
                    ]],
                ]
            );

            if (!$response->successful()) {
                Log::warning('Clarifai failed: ' . $response->status() . ' ' . $response->body());
                return ['detected_foods' => []];
            }

            $concepts = $response->json()['outputs'][0]['data']['concepts'] ?? [];
            Log::info('Clarifai raw concepts: ' . collect($concepts)->pluck('name')->take(8)->implode(', '));

            // Lower threshold to 0.50 so more foods are matched
            $detectedFoods = [];
            foreach (array_slice($concepts, 0, 8) as $concept) {
                if ($concept['value'] < 0.50) continue;

                $foodName  = $concept['name'];
                $nutrition = $this->getNutritionForFood($foodName);

                if ($nutrition) {
                    $detectedFoods[] = array_merge($nutrition, [
                        'confidence' => round($concept['value'] * 100, 1),
                    ]);
                }

                if (count($detectedFoods) >= 3) break;
            }

            if (!empty($detectedFoods)) {
                Log::info('Clarifai success: ' . count($detectedFoods) . ' foods detected');
                return [
                    'detected_foods' => $detectedFoods,
                    'total_items'    => count($detectedFoods),
                    'ai_status'      => 'clarifai',
                ];
            }

            // Clarifai found concepts but none matched our nutrition table — log them
            if (!empty($concepts)) {
                $names = collect($concepts)->pluck('name')->take(5)->implode(', ');
                Log::info("Clarifai found concepts but none matched nutrition table: {$names}");

                // Build a basic entry from the top concept anyway so the user sees something real
                $top = $concepts[0];
                $detectedFoods[] = [
                    'food_name'         => ucwords($top['name']),
                    'fatsecret_food_id' => 'clarifai_unmatched',
                    'brand_name'        => 'AI Detected',
                    'serving_size'      => 200,
                    'serving_unit'      => 'g',
                    'calories'          => 300,
                    'protein'           => 15,
                    'carbs'             => 35,
                    'fat'               => 10,
                    'fiber'             => 3,
                    'sugar'             => 5,
                    'sodium'            => 300,
                    'confidence'        => round($top['value'] * 100, 1),
                ];
                return [
                    'detected_foods' => $detectedFoods,
                    'total_items'    => 1,
                    'ai_status'      => 'clarifai_partial',
                ];
            }

        } catch (\Exception $e) {
            Log::error('Clarifai Exception: ' . $e->getMessage());
        }

        return ['detected_foods' => []];
    }

    /**
     * Look up nutrition for a food name using FatSecret or built-in estimates
     */
    private function getNutritionForFood(string $foodName): ?array
    {
        // Built-in common food nutrition table (calories per 100g)
        $nutritionTable = [
            'rice'           => ['calories'=>130,'protein'=>2.7,'carbs'=>28.2,'fat'=>0.3,'fiber'=>0.4,'sugar'=>0.1,'sodium'=>1],
            'chicken'        => ['calories'=>165,'protein'=>31,'carbs'=>0,'fat'=>3.6,'fiber'=>0,'sugar'=>0,'sodium'=>74],
            'beef'           => ['calories'=>250,'protein'=>26,'carbs'=>0,'fat'=>17,'fiber'=>0,'sugar'=>0,'sodium'=>72],
            'egg'            => ['calories'=>155,'protein'=>13,'carbs'=>1.1,'fat'=>11,'fiber'=>0,'sugar'=>1.1,'sodium'=>124],
            'noodle'         => ['calories'=>138,'protein'=>4.5,'carbs'=>25,'fat'=>2.3,'fiber'=>1.5,'sugar'=>0.6,'sodium'=>400],
            'bread'          => ['calories'=>265,'protein'=>9,'carbs'=>49,'fat'=>3.2,'fiber'=>2.7,'sugar'=>5,'sodium'=>491],
            'salad'          => ['calories'=>20,'protein'=>1.8,'carbs'=>3.9,'fat'=>0.2,'fiber'=>2,'sugar'=>2,'sodium'=>15],
            'pasta'          => ['calories'=>158,'protein'=>5.8,'carbs'=>31,'fat'=>0.9,'fiber'=>1.8,'sugar'=>0.6,'sodium'=>1],
            'pizza'          => ['calories'=>266,'protein'=>11,'carbs'=>33,'fat'=>10,'fiber'=>2.3,'sugar'=>3.6,'sodium'=>598],
            'burger'         => ['calories'=>295,'protein'=>17,'carbs'=>24,'fat'=>14,'fiber'=>1,'sugar'=>5,'sodium'=>396],
            'soup'           => ['calories'=>60,'protein'=>3.5,'carbs'=>7,'fat'=>1.5,'fiber'=>1,'sugar'=>2,'sodium'=>820],
            'fish'           => ['calories'=>136,'protein'=>26,'carbs'=>0,'fat'=>3.5,'fiber'=>0,'sugar'=>0,'sodium'=>86],
            'vegetable'      => ['calories'=>35,'protein'=>2,'carbs'=>7,'fat'=>0.3,'fiber'=>3,'sugar'=>4,'sodium'=>50],
            'fruit'          => ['calories'=>52,'protein'=>0.5,'carbs'=>14,'fat'=>0.2,'fiber'=>2.4,'sugar'=>10,'sodium'=>1],
            'milk'           => ['calories'=>61,'protein'=>3.2,'carbs'=>4.8,'fat'=>3.3,'fiber'=>0,'sugar'=>5.1,'sodium'=>43],
            'cheese'         => ['calories'=>402,'protein'=>25,'carbs'=>1.3,'fat'=>33,'fiber'=>0,'sugar'=>0.5,'sodium'=>621],
            'potato'         => ['calories'=>77,'protein'=>2,'carbs'=>17,'fat'=>0.1,'fiber'=>2.2,'sugar'=>0.8,'sodium'=>6],
            'sandwich'       => ['calories'=>250,'protein'=>12,'carbs'=>32,'fat'=>8,'fiber'=>2,'sugar'=>4,'sodium'=>550],
            'curry'          => ['calories'=>180,'protein'=>12,'carbs'=>15,'fat'=>8,'fiber'=>2,'sugar'=>4,'sodium'=>600],
            'steak'          => ['calories'=>271,'protein'=>26,'carbs'=>0,'fat'=>18,'fiber'=>0,'sugar'=>0,'sodium'=>58],
            'shrimp'         => ['calories'=>99,'protein'=>24,'carbs'=>0.2,'fat'=>0.3,'fiber'=>0,'sugar'=>0,'sodium'=>111],
            'tofu'           => ['calories'=>76,'protein'=>8,'carbs'=>1.9,'fat'=>4.8,'fiber'=>0.3,'sugar'=>0.7,'sodium'=>7],
            'cake'           => ['calories'=>350,'protein'=>5,'carbs'=>53,'fat'=>14,'fiber'=>1,'sugar'=>35,'sodium'=>300],
            'chocolate'      => ['calories'=>546,'protein'=>5,'carbs'=>60,'fat'=>31,'fiber'=>7,'sugar'=>48,'sodium'=>24],
            'coffee'         => ['calories'=>2,'protein'=>0.3,'carbs'=>0,'fat'=>0,'fiber'=>0,'sugar'=>0,'sodium'=>2],
            'juice'          => ['calories'=>45,'protein'=>0.7,'carbs'=>11,'fat'=>0.2,'fiber'=>0.2,'sugar'=>9,'sodium'=>1],
        ];

        $foodNameLower = strtolower($foodName);

        // Try fuzzy match against our table
        foreach ($nutritionTable as $key => $nutrition) {
            if (str_contains($foodNameLower, $key) || str_contains($key, $foodNameLower)) {
                return array_merge([
                    'food_name'         => ucwords($foodName),
                    'fatsecret_food_id' => 'clarifai_' . $key,
                    'brand_name'        => 'AI Detected',
                    'serving_size'      => 200,
                    'serving_unit'      => 'g',
                ], $nutrition);
            }
        }

        // Try FatSecret search as fallback
        try {
            $results = $this->fatSecretService->searchFood($foodName, 0, 1);
            if (isset($results['foods']['food'][0])) {
                $food = $results['foods']['food'][0];
                $desc = $food['food_description'] ?? '';
                return [
                    'food_name'         => $food['food_name'],
                    'fatsecret_food_id' => $food['food_id'],
                    'brand_name'        => $food['brand_name'] ?? 'FatSecret',
                    'serving_size'      => 100,
                    'serving_unit'      => 'g',
                    'calories'          => $this->extractNum($desc, 'Calories'),
                    'protein'           => $this->extractNum($desc, 'Protein'),
                    'carbs'             => $this->extractNum($desc, 'Carbs'),
                    'fat'               => $this->extractNum($desc, 'Fat'),
                    'fiber'             => 0, 'sugar' => 0, 'sodium' => 0,
                ];
            }
        } catch (\Exception $e) { /* FatSecret unavailable */ }

        return null;
    }

    private function extractNum(string $text, string $key): float
    {
        preg_match("/{$key}:\s*([\d.]+)/i", $text, $m);
        return isset($m[1]) ? (float)$m[1] : 0;
    }


    /**
     * Gemini Vision — tries latest models with fresh API key
     */
    private function analyzeWithGemini($imagePath)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey || !file_exists($imagePath)) {
            Log::warning('Gemini: no API key or file missing');
            return ['detected_foods' => []];
        }

        try {
            $imageContent = base64_encode(file_get_contents($imagePath));
            $mimeType     = mime_content_type($imagePath) ?: 'image/jpeg';

            // Ultra-detailed prompt for comprehensive food detection like professional nutrition apps
            $prompt = 'You are an expert nutritionist analyzing a food photo for a calorie tracking app. '
                . 'Your task is to identify EVERY SINGLE food item visible, including small items like garnishes, sauces, and condiments. '
                . "\n\n"
                . 'CRITICAL REQUIREMENTS:\n'
                . '1. Identify ALL items separately - rice, meat, vegetables, sauces, garnishes, sides, everything\n'
                . '2. For Asian meals: detect rice, main protein, egg, vegetables, sambal/sauce, fried items, peanuts, cucumber, etc.\n'
                . '3. Be VERY specific with names:\n'
                . '   - "Fried Chicken Piece" not "Chicken"\n'
                . '   - "Hard Boiled Egg" not "Egg"\n'
                . '   - "Steamed White Rice" not "Rice"\n'
                . '   - "Sambal" for chili sauce\n'
                . '   - "Fried Anchovies" for ikan bilis\n'
                . '   - "Roasted Peanuts" for peanuts\n'
                . '   - "Cucumber Slices" for cucumber\n'
                . '4. Estimate realistic portion sizes:\n'
                . '   - Rice: 150-300g (1-2 cups)\n'
                . '   - Chicken piece: 80-150g\n'
                . '   - Egg: 50-60g (1 egg)\n'
                . '   - Vegetables: 50-100g\n'
                . '   - Sambal/sauce: 20-50g (1-2 tbsp)\n'
                . '   - Peanuts: 20-30g\n'
                . '   - Cucumber: 50-80g\n'
                . '5. Calculate accurate nutrition for EACH item based on its portion\n'
                . '6. Include even small items - they add up!\n'
                . "\n"
                . 'NUTRITION CALCULATION GUIDELINES:\n'
                . '- Fried chicken (100g): ~290 kcal, 25g protein, 0g carbs, 18g fat\n'
                . '- Hard boiled egg (50g): ~70 kcal, 6g protein, 0.5g carbs, 5g fat\n'
                . '- White rice (100g): ~130 kcal, 2.7g protein, 28g carbs, 0.3g fat\n'
                . '- Sambal (20g): ~30 kcal, 0.5g protein, 3g carbs, 2g fat\n'
                . '- Fried anchovies (20g): ~100 kcal, 13g protein, 0g carbs, 5g fat\n'
                . '- Peanuts (20g): ~115 kcal, 5g protein, 3g carbs, 10g fat\n'
                . '- Cucumber (50g): ~8 kcal, 0.3g protein, 2g carbs, 0.1g fat\n'
                . "\n"
                . 'Return ONLY a JSON array with this EXACT format (no markdown, no extra text):\n'
                . '[{"food_name":"Specific Food Name","serving_size":150,"serving_unit":"g","calories":250,"protein":30.0,"carbs":15.0,"fat":8.0,"fiber":2.0,"sugar":1.0,"sodium":400}]\n'
                . "\n"
                . 'EXAMPLE for Nasi Lemak plate:\n'
                . '[{"food_name":"Steamed White Rice","serving_size":200,"serving_unit":"g","calories":260,"protein":5.4,"carbs":56,"fat":0.6,"fiber":0.8,"sugar":0.2,"sodium":2},'
                . '{"food_name":"Fried Chicken Piece","serving_size":120,"serving_unit":"g","calories":348,"protein":30,"carbs":0,"fat":21.6,"fiber":0,"sugar":0,"sodium":180},'
                . '{"food_name":"Hard Boiled Egg","serving_size":50,"serving_unit":"g","calories":70,"protein":6,"carbs":0.5,"fat":5,"fiber":0,"sugar":0.5,"sodium":62},'
                . '{"food_name":"Sambal","serving_size":30,"serving_unit":"g","calories":45,"protein":0.8,"carbs":4.5,"fat":3,"fiber":1,"sugar":2,"sodium":200},'
                . '{"food_name":"Fried Anchovies","serving_size":20,"serving_unit":"g","calories":100,"protein":13,"carbs":0,"fat":5,"fiber":0,"sugar":0,"sodium":400},'
                . '{"food_name":"Roasted Peanuts","serving_size":20,"serving_unit":"g","calories":115,"protein":5,"carbs":3,"fat":10,"fiber":2,"sugar":1,"sodium":90},'
                . '{"food_name":"Cucumber Slices","serving_size":50,"serving_unit":"g","calories":8,"protein":0.3,"carbs":2,"fat":0.1,"fiber":0.5,"sugar":1,"sodium":1}]\n'
                . "\n"
                . 'If no food is visible, return: []';

            $payload = [
                'contents' => [[
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageContent]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature'     => 0.2,  // Lower temperature for more consistent, accurate results
                    'maxOutputTokens' => 4096, // Increased to allow detailed list of many food items
                    'topP'            => 0.8,  // More focused responses
                    'topK'            => 20,   // More deterministic
                ],
            ];

            // Models to try in order — using latest Gemini models
            $models = [
                ['model' => 'gemini-2.5-flash', 'api' => 'v1beta'],
                ['model' => 'gemini-2.0-flash', 'api' => 'v1beta'],
            ];

            foreach ($models as $m) {
                $url      = "https://generativelanguage.googleapis.com/{$m['api']}/models/{$m['model']}:generateContent?key={$apiKey}";
                $response = Http::timeout(30)->post($url, $payload);

                if ($response->successful()) {
                    $result = $this->parseGeminiFoodArray($response->json());
                    if (!empty($result['detected_foods'])) {
                        Log::info("Gemini success — model: {$m['model']}, foods: " . count($result['detected_foods']));
                        return $result;
                    }
                    Log::warning("Gemini {$m['model']} returned empty food list");
                    continue;
                }

                $errMsg = $response->json()['error']['message'] ?? 'unknown';
                Log::warning("Gemini {$m['model']} HTTP {$response->status()}: {$errMsg}");

                // Only retry on quota/rate limit errors
                if ($response->status() !== 429 && !str_contains(strtolower($errMsg), 'quota')) {
                    break;
                }
            }

            // All models failed or returned empty
            Log::info('Gemini could not detect foods');
            return ['detected_foods' => []];

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return ['detected_foods' => []];
        }
    }

    /**
     * Parse Gemini raw text response into detected_foods array
     * Handles: JSON arrays, {"foods":[...]}, {"detected_foods":[...]}, single objects
     */
    private function parseGeminiFoodArray(array $geminiData): array
    {
        $raw = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        Log::info('Gemini raw response: ' . $raw);

        // Strip markdown code fences
        $text = preg_replace('/```[a-z]*\s*/i', '', $raw);
        $text = str_replace('```', '', $text);
        $text = trim($text);

        $items = [];

        // Try extracting a JSON array first
        if (preg_match('/\[[\s\S]*\]/s', $text, $m)) {
            $arr = json_decode($m[0], true);
            if (is_array($arr)) {
                $items = $arr;
                Log::info('Gemini parsed ' . count($items) . ' food items from JSON array');
            }
        }

        // Try extracting a JSON object ({"foods":[...], etc.)
        if (empty($items) && preg_match('/\{[\s\S]*\}/s', $text, $m)) {
            $obj = json_decode($m[0], true);
            if (is_array($obj)) {
                $items = $obj['foods']
                      ?? $obj['detected_foods']
                      ?? $obj['items']
                      ?? (isset($obj['food_name']) ? [$obj] : []);
                Log::info('Gemini parsed ' . count($items) . ' food items from JSON object');
            }
        }

        if (empty($items)) {
            Log::warning('Gemini: could not extract any food items from response');
            return ['detected_foods' => [], 'total_items' => 0, 'ai_status' => 'empty'];
        }

        $foods = [];
        foreach ($items as $food) {
            if (!is_array($food)) continue;
            $name = $food['food_name'] ?? $food['name'] ?? '';
            if (empty($name)) continue;

            $servingSize = (float) ($food['serving_size'] ?? 100);
            $calories = (int) round($food['calories'] ?? 0);
            
            Log::info("Detected: {$name} - {$servingSize}g - {$calories} kcal");

            $foods[] = [
                'food_name'         => $name,
                'fatsecret_food_id' => 'gemini_ai',
                'brand_name'        => 'AI Detected',
                'confidence'        => (int)   ($food['confidence'] ?? 85),
                'serving_size'      => $servingSize,
                'serving_unit'      => $food['serving_unit'] ?? 'g',
                'calories'          => $calories,
                'protein'           => (float) round($food['protein']  ?? 0, 1),
                'carbs'             => (float) round($food['carbs']    ?? $food['carbohydrates'] ?? 0, 1),
                'fat'               => (float) round($food['fat']      ?? 0, 1),
                'fiber'             => (float) round($food['fiber']    ?? 0, 1),
                'sugar'             => (float) round($food['sugar']    ?? 0, 1),
                'sodium'            => (float) round($food['sodium']   ?? 0, 1),
            ];
        }

        Log::info('Gemini final result: ' . count($foods) . ' foods with total ' . array_sum(array_column($foods, 'calories')) . ' kcal');

        return [
            'detected_foods' => $foods,
            'total_items'    => count($foods),
            'ai_status'      => 'gemini_success',
        ];
    }

    /**
     * Smart generic meal estimator — used when Gemini quota is exceeded.
     * Gives the user SOMETHING useful rather than "0 kcal, No foods detected".
     */
    private function estimateGenericMeal(): array
    {
        return [
            'detected_foods' => [
                [
                    'food_name'         => 'Mixed Meal (Estimated)',
                    'fatsecret_food_id' => 'estimated',
                    'brand_name'        => 'Calorie Estimator',
                    'confidence'        => 60,
                    'serving_size'      => 300,
                    'serving_unit'      => 'g',
                    'calories'          => 450,
                    'protein'           => 25,
                    'carbs'             => 50,
                    'fat'               => 15,
                    'fiber'             => 5,
                    'sugar'             => 8,
                    'sodium'            => 600,
                ],
            ],
            'total_items' => 1,
            'ai_status'   => 'estimated',
            'hint'        => 'AI quota exceeded — showing estimated values. Tap a food item to edit for accuracy.',
        ];
    }

    /* ──────────────────────────────────────────────────────────────
     * The methods below are kept for reference / alternative APIs.
     * ────────────────────────────────────────────────────────────── */

    private function analyzeWithGoogleVision($imagePath)
    {
        $apiKey = env('GOOGLE_VISION_API_KEY');
        if (!$apiKey || !file_exists($imagePath)) {
            return ['detected_foods' => []];
        }

        try {
            $imageContent = base64_encode(file_get_contents($imagePath));
            $response = Http::timeout(20)->post("https://vision.googleapis.com/v1/images:annotate?key={$apiKey}", [
                'requests' => [[
                    'image'    => ['content' => $imageContent],
                    'features' => [['type' => 'LABEL_DETECTION', 'maxResults' => 10]],
                ]],
            ]);
            
            if (!$response->successful()) {
                Log::warning('Google Vision failed: ' . $response->status() . ' ' . $response->body());
                return ['detected_foods' => []];
            }
            
            return $this->processGoogleVisionResults($response->json());
        } catch (\Exception $e) {
            Log::error('Google Vision Exception: ' . $e->getMessage());
        }
        return ['detected_foods' => []];
    }

    private function processGoogleVisionResults($visionData): array
    {
        $labels = $visionData['responses'][0]['labelAnnotations'] ?? [];
        Log::info('Google Vision raw labels: ' . collect($labels)->pluck('description')->take(8)->implode(', '));

        $detectedFoods = [];
        foreach ($labels as $label) {
            if ($label['score'] < 0.60) continue; // Lower threshold to 60%

            $foodName = $label['description'];
            
            // Try built-in nutrition table first
            $nutrition = $this->getNutritionForFood($foodName);
            
            if ($nutrition) {
                $detectedFoods[] = array_merge($nutrition, [
                    'confidence' => round($label['score'] * 100, 1),
                ]);
            }

            if (count($detectedFoods) >= 3) break;
        }

        if (!empty($detectedFoods)) {
            Log::info('Google Vision success: ' . count($detectedFoods) . ' foods detected');
            return [
                'detected_foods' => $detectedFoods,
                'total_items'    => count($detectedFoods),
                'ai_status'      => 'google_vision',
            ];
        }

        return ['detected_foods' => []];
    }

    private function extractCalories($food): int
    {
        if (isset($food['food_description'])) {
            preg_match('/Calories: (\d+)/', $food['food_description'], $m);
            return isset($m[1]) ? (int)$m[1] : 0;
        }
        return 0;
    }

    private function extractNutrient($food, $nutrient): float
    {
        if (isset($food['food_description'])) {
            preg_match("/{$nutrient}: ([\d.]+)g/i", $food['food_description'], $m);
            return isset($m[1]) ? (float)$m[1] : 0;
        }
        return 0;
    }

    private function getSearchTerms(string $foodName): array
    {
        $map = [
            'chicken' => ['chicken breast', 'grilled chicken'], 'rice' => ['white rice', 'cooked rice'],
            'fish'    => ['salmon', 'tilapia'],                 'egg'  => ['boiled egg', 'fried egg'],
            'pasta'   => ['cooked pasta', 'spaghetti'],         'bread'=> ['white bread', 'whole wheat bread'],
        ];
        return $map[strtolower($foodName)] ?? [$foodName];
    }
}
