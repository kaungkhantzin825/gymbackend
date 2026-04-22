<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiApiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
    }

    /**
     * Generate a workout plan based on user params
     */
    public function generateWorkoutPlan(array $userProfile, array $params): array
    {
        if (empty($this->apiKey)) {
            // Demo mode if no key added
            return $this->getDemoWorkout();
        }

        $prompt = "Create a workout plan for a user with the following profile: "
            . json_encode($userProfile) . ". "
            . "They have this equipment available: {$params['equipment_available']}. "
            . "They have {$params['time_available_minutes']} minutes to train. "
            . "Focus area is {$params['focus_area']}. "
            . "Return ONLY a valid JSON object matching this structure:
              {
                'workout_name': 'String',
                'estimated_duration_minutes': Int,
                'exercises': [
                  {
                    'name': 'String',
                    'sets': Int,
                    'reps': 'String (e.g., 8-10)',
                    'rest_seconds': Int,
                    'notes': 'String'
                  }
                ]
              }";

        $response = Http::post("{$this->baseUrl}gemini-1.5-flash:generateContent?key={$this->apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Clean markdown markdown blocks from the JSON
            $text = str_replace(['```json', '```'], '', $text);
            return json_decode(trim($text), true) ?? $this->getDemoWorkout();
        }

        return $this->getDemoWorkout();
    }

    /**
     * Generate content using Gemini API with a custom prompt
     */
    public function generateContent(string $prompt): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Gemini API key is not configured');
        }

        // Use gemini-2.5-flash - it's the latest and fastest model available
        $model = 'gemini-2.5-flash';
        
        $response = Http::timeout(60)->post(
            "{$this->baseUrl}{$model}:generateContent?key={$this->apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'topK'           => 40,
                    'topP'           => 0.95,
                    'maxOutputTokens' => 8192,
                ]
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Gemini API request failed: ' . $response->body());
        }

        return $response->json();
    }

    private function getDemoWorkout(): array
    {
        return [
            'workout_name' => 'AI Generated Full Body (Demo)',
            'estimated_duration_minutes' => 45,
            'exercises' => [
                [
                    'name' => 'Dumbbell Goblet Squat',
                    'sets' => 3,
                    'reps' => '10-12',
                    'rest_seconds' => 90,
                    'notes' => 'Keep your chest up and core tight.'
                ],
                [
                    'name' => 'Push-ups',
                    'sets' => 3,
                    'reps' => 'Until failure',
                    'rest_seconds' => 60,
                    'notes' => 'Modify on knees if needed.'
                ]
            ]
        ];
    }
}
