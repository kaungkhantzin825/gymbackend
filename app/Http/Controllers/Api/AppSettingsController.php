<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingsController extends Controller
{
    /**
     * Get About content
     */
    public function getAbout()
    {
        $content = AppSetting::get('about', 'No content available');
        return response()->json(['content' => $content]);
    }

    /**
     * Get Privacy Policy content
     */
    public function getPrivacyPolicy()
    {
        $content = AppSetting::get('privacy_policy', 'No content available');
        return response()->json(['content' => $content]);
    }
}
