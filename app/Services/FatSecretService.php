<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FatSecretService
{
    private $consumerKey;
    private $consumerSecret;
    private $apiUrl;

    public function __construct()
    {
        $this->consumerKey = env('FATSECRET_CLIENT_ID');
        $this->consumerSecret = env('FATSECRET_CLIENT_SECRET');
        $this->apiUrl = 'https://platform.fatsecret.com/rest/server.api';
    }

    /**
     * Generate OAuth 1.0 signature
     */
    private function generateOAuthSignature($method, $url, $params)
    {
        // Add OAuth parameters
        $oauthParams = [
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_nonce' => md5(microtime() . mt_rand()),
            'oauth_version' => '1.0'
        ];

        // Merge all parameters
        $allParams = array_merge($params, $oauthParams);
        
        // Sort parameters
        ksort($allParams);
        
        // Build parameter string
        $paramString = http_build_query($allParams, '', '&', PHP_QUERY_RFC3986);
        
        // Build base string
        $baseString = strtoupper($method) . '&' . 
                     rawurlencode($url) . '&' . 
                     rawurlencode($paramString);
        
        // Generate signature
        $signingKey = rawurlencode($this->consumerSecret) . '&';
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));
        
        // Add signature to OAuth parameters
        $oauthParams['oauth_signature'] = $signature;
        
        return $oauthParams;
    }

    /**
     * Make OAuth 1.0 request
     */
    private function makeOAuthRequest($params)
    {
        $method = 'GET';
        $url = $this->apiUrl;
        
        // Generate OAuth signature
        $oauthParams = $this->generateOAuthSignature($method, $url, $params);
        
        // Merge all parameters
        $allParams = array_merge($params, $oauthParams);
        
        // Make request
        $response = Http::get($url, $allParams);
        
        return $response->json();
    }

    /**
     * Search food by name
     */
    public function searchFood($query, $page = 0, $maxResults = 20)
    {
        $params = [
            'method' => 'foods.search',
            'search_expression' => $query,
            'page_number' => $page,
            'max_results' => $maxResults,
            'format' => 'json'
        ];

        $result = $this->makeOAuthRequest($params);
        
        // Check for errors
        if (isset($result['error'])) {
            Log::warning('FatSecret API error', [
                'query' => $query,
                'error' => $result['error']
            ]);
            return ['foods' => []];
        }
        
        Log::info('FatSecret search', [
            'query' => $query,
            'has_foods' => isset($result['foods']['food'])
        ]);

        return $result;
    }

    /**
     * Get food details by ID
     */
    public function getFoodById($foodId)
    {
        $params = [
            'method' => 'food.get.v2',
            'food_id' => $foodId,
            'format' => 'json'
        ];

        return $this->makeOAuthRequest($params);
    }

    /**
     * Search food by barcode
     */
    public function searchByBarcode($barcode)
    {
        $params = [
            'method' => 'food.find_id_for_barcode',
            'barcode' => $barcode,
            'format' => 'json'
        ];

        return $this->makeOAuthRequest($params);
    }

    /**
     * Get autocomplete suggestions
     */
    public function autocomplete($expression)
    {
        $params = [
            'method' => 'foods.autocomplete',
            'expression' => $expression,
            'format' => 'json'
        ];

        return $this->makeOAuthRequest($params);
    }
}
