<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number',
        ]);

        try {
            $result = $this->authService->registerWithEmail($request->only([
                'name', 'email', 'password', 'phone_number'
            ]));

            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Login user with email and password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->authService->loginWithEmail($request->only(['email', 'password']));
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'errors' => $e->errors()
            ], 401);
        }
    }

    /**
     * Redirect to Google OAuth.
     */
    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function googleCallback()
    {
        try {
            $result = $this->authService->handleGoogleCallback();
            
            // In a SPA, you might want to redirect to frontend with token
            // return redirect()->to(config('app.frontend_url') . '/auth/callback?token=' . $result['token']);
            
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Google authentication failed',
                'errors' => $e->errors()
            ], 401);
        }
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $result = $this->authService->verifyOtp($request->email, $request->otp);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'OTP verification failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Resend OTP code.
     */
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        try {
            $result = $this->authService->resendOtp($request->email);
            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Failed to resend OTP',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Update user role and create profile.
     */
    public function updateRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:patient,doctor,pharmacy',
            'profile_data' => 'sometimes|array'
        ]);

        try {
            $user = $this->authService->updateUserRole(
                $request->user()->id,
                $request->role,
                $request->profile_data ?? []
            );

            return response()->json([
                'user' => $user,
                'message' => 'Role updated successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['patientProfile', 'doctorProfile', 'pharmacyProfile'])
        ]);
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }
}
