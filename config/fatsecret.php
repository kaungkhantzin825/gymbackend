<?php

return [
    'client_id' => env('FATSECRET_CLIENT_ID'),
    'client_secret' => env('FATSECRET_CLIENT_SECRET'),
    'api_url' => env('FATSECRET_API_URL', 'https://platform.fatsecret.com/rest/server.api'),
    'oauth_url' => 'https://oauth.fatsecret.com/connect/token',
];
