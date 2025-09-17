<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\SearchController;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\DrugInteractionController;
use App\Http\Controllers\Api\AlternativeSearchController;
use App\Http\Controllers\Api\PharmacyController;

// Simple login route for testing
Route::post('login', [AuthController::class, 'login']);


//search Route
Route::get('/search', [SearchController::class, 'search']);
Route::post('search/with-alternatives', [AlternativeSearchController::class, 'search']);

//get all pharmacies
Route::get('pharmacies', [PharmacyController::class, 'index']);

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


    // Donation routes - require authentication
    Route::middleware(['auth:sanctum'])->group(function () {
        // Medicines search (authenticated, minimal data)
        Route::get('medicines/search', [MedicineController::class, 'search']);

        // User's own donations
        Route::get('donations', [DonationController::class, 'index']);
        Route::post('donations', [DonationController::class, 'store']);
        Route::get('donations/{id}', [DonationController::class, 'show']);
        Route::put('donations/{id}', [DonationController::class, 'update']);
        Route::delete('donations/{id}', [DonationController::class, 'destroy']);
        
        // Public donations (for searching)
        Route::get('donations-available', [DonationController::class, 'available']);
    });

    // Admin/Public routes for viewing all donations
    Route::get('donations-all', [DonationController::class, 'all']);


    // Example of using the verified middleware for protected routes
    // Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    //     // Routes that require verified email
    // });
});
