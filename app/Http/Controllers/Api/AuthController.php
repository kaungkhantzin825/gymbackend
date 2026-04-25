<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected TwilioService $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:user,admin',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
        ]);

        // Create default profile
        UserProfile::create([
            'user_id' => $user->id,
            'goal' => 'maintain',
            'activity_level' => 'moderate',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // ─── Phone Login with Twilio ─────────────────────────────────────────────

    /**
     * Send OTP to phone number
     */
    public function sendPhoneOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/', // E.164 format
        ]);

        $result = $this->twilioService->sendOTP($request->phone);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Verify OTP and login/register user
     */
    public function verifyPhoneOTP(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
            'name' => 'nullable|string|max:255',
        ]);

        // Verify OTP
        if (!$this->twilioService->verifyOTP($request->phone, $request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        // Find or create user
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $request->name ?? 'User',
                'phone' => $request->phone,
                'email' => $request->phone . '@phone.gymapp.com', // Dummy email
                'password' => Hash::make(Str::random(32)), // Random password
                'role' => 'user',
            ]);

            // Create default profile
            UserProfile::create([
                'user_id' => $user->id,
                'goal' => 'maintain',
                'activity_level' => 'moderate',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => !$user->wasRecentlyCreated,
        ]);
    }

    // ─── Google Login ────────────────────────────────────────────────────────

    /**
     * Login/Register with Google
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
            'name' => 'required|string',
            'email' => 'required|email',
            'photo_url' => 'nullable|string',
        ]);

        // Find or create user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)), // Random password
                'google_id' => $request->id_token,
                'profile_photo' => $request->photo_url,
                'role' => 'user',
            ]);

            // Create default profile
            UserProfile::create([
                'user_id' => $user->id,
                'goal' => 'maintain',
                'activity_level' => 'moderate',
            ]);
        } else {
            // Update Google ID if not set
            if (!$user->google_id) {
                $user->update(['google_id' => $request->id_token]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }

    // ─── Forgot Password ─────────────────────────────────────────────────────

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link',
        ], 400);
    }

    /**
     * Reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to reset password',
        ], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('profile');
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'role' => $user->role,
            'profile_photo' => $user->profile_photo ? url('storage/' . $user->profile_photo) : null,
            'profile' => $user->profile,
        ]);
    }
}
