<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TwilioService
{
    protected ?string $accountSid;
    protected ?string $authToken;
    protected ?string $phoneNumber;

    public function __construct()
    {
        $this->accountSid = env('TWILIO_ACCOUNT_SID');
        $this->authToken = env('TWILIO_AUTH_TOKEN');
        $this->phoneNumber = env('TWILIO_PHONE_NUMBER');
        
        // Validate Twilio credentials
        if (!$this->accountSid || !$this->authToken || !$this->phoneNumber) {
            Log::warning('Twilio credentials not configured in .env file');
        }
    }

    /**
     * Send OTP via SMS
     */
    public function sendOTP(string $phoneNumber): array
    {
        // Check if Twilio is configured
        if (!$this->accountSid || !$this->authToken || !$this->phoneNumber) {
            Log::error('Twilio credentials not configured');
            return [
                'success' => false,
                'message' => 'SMS service not configured. Please contact administrator.',
            ];
        }
        
        try {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in cache for 5 minutes
            $cacheKey = "otp_{$phoneNumber}";
            Cache::put($cacheKey, $otp, now()->addMinutes(5));
            
            // Send SMS via Twilio
            $message = "Your GymApp verification code is: {$otp}. Valid for 5 minutes.";
            $this->sendSMS($phoneNumber, $message);
            
            Log::info("OTP sent to {$phoneNumber}: {$otp}");
            
            return [
                'success' => true,
                'message' => 'OTP sent successfully',
                'expires_in' => 300, // 5 minutes in seconds
            ];
        } catch (\Exception $e) {
            Log::error("Failed to send OTP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send OTP: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOTP(string $phoneNumber, string $otp): bool
    {
        $cacheKey = "otp_{$phoneNumber}";
        $storedOTP = Cache::get($cacheKey);
        
        if (!$storedOTP) {
            Log::warning("OTP expired or not found for {$phoneNumber}");
            return false;
        }
        
        if ($storedOTP === $otp) {
            // Clear OTP after successful verification
            Cache::forget($cacheKey);
            Log::info("OTP verified successfully for {$phoneNumber}");
            return true;
        }
        
        Log::warning("Invalid OTP for {$phoneNumber}");
        return false;
    }

    /**
     * Send SMS via Twilio API
     */
    protected function sendSMS(string $to, string $message): void
    {
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        
        $data = [
            'From' => $this->phoneNumber,
            'To' => $to,
            'Body' => $message,
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new \Exception("Twilio API error: {$response}");
        }
        
        Log::info("SMS sent successfully to {$to}");
    }
}
