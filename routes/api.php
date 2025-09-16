<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\DrugInteractionController;
use App\Http\Controllers\Api\AlternativeSearchController;


// Simple login route for testing
Route::post('login', [AuthController::class, 'login']);

// Simple login route for testing
Route::post('login', [AuthController::class, 'login']);

//search Route
Route::get('/search', [SearchController::class, 'search']);
Route::post('search/with-alternatives', [AlternativeSearchController::class, 'search']);

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        // Public routes
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        // OTP routes - accessible both publicly and for authenticated users
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('resend-otp', [AuthController::class, 'resendOtp']);

        // Password reset routes - public access
        Route::post('send-password-reset-otp', [AuthController::class, 'sendPasswordResetOtp']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        // Google OAuth
        Route::get('google/redirect', [AuthController::class, 'googleRedirect']);
        Route::get('google/callback', [AuthController::class, 'googleCallback']);

        // Protected routes
        Route::middleware('auth:sanctum')->group(function () {
            // Authenticated (no verification required)
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);

            // Authenticated + email verified
            Route::middleware('verified')->group(function () {
                Route::post('update-role', [AuthController::class, 'updateRole']);
            });
        });
        //Route::post('/check-interaction', [InteractionController::class, 'check']);
        Route::get('/suggest-drugs', [InteractionController::class, 'suggest']);
        Route::post('/check-interaction', [DrugInteractionController::class, 'checkInteraction']);
    });


    // Example of using the verified middleware for protected routes
    // Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    //     // Routes that require verified email
    // });
});
