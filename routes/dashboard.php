<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\DonationController;
use App\Http\Controllers\Api\Dashboard\MedicineController;
use App\Http\Controllers\Api\Dashboard\OrderController;
use App\Http\Controllers\Api\Dashboard\PrescriptionController;
use App\Http\Controllers\Api\Dashboard\SettingController;
use App\Http\Controllers\Api\Dashboard\UserController;
use App\Http\Controllers\Api\Dashboard\ActiveIngredientController;
use App\Http\Controllers\Api\Dashboard\TherapeuticClassController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('/', [DashboardController::class, 'overview'])->name('dashboard');
    Route::get('/summary', [DashboardController::class, 'summary']);

    Route::get('/charts', [DashboardController::class, 'chartsData']);
    Route::get('/charts/medicine-shortage', [DashboardController::class, 'medicineShortageChart']);
    Route::get('/charts/daily-orders', [DashboardController::class, 'dailyOrdersChart']);
    Route::get('/charts/user-roles', [DashboardController::class, 'userRoleChart']);

    Route::get('/search', [DashboardController::class, 'globalSearch']);
    Route::get('/search/users', [DashboardController::class, 'searchUsers']);
    Route::get('/search/medicines', [DashboardController::class, 'searchMedicines']);

    Route::get('medicines', [MedicineController::class, 'index']);
    Route::get('medicines/stats', [MedicineController::class, 'stats']);
    Route::post('medicines', [MedicineController::class, 'store']);
    Route::put('medicines/{medicine}', [MedicineController::class, 'update']);
    Route::post('medicines/confirm-correction', [MedicineController::class, 'confirmCorrection']);
    Route::delete('medicines/{medicine}', [MedicineController::class, 'destroy']);
    Route::post('medicines/{id}/restore', [MedicineController::class, 'restore']);
    Route::get('medicines/{medicine}', [MedicineController::class, 'show']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/stats', [OrderController::class, 'stats']);
    Route::get('orders/my', [OrderController::class, 'myOrders']);
    Route::put('orders/{order}', [OrderController::class, 'update']);
    Route::delete('orders/{order}', [OrderController::class, 'destroy']);
    Route::post('orders/{id}/restore', [OrderController::class, 'restore']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/stats', [UserController::class, 'stats']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::post('users/{id}/restore', [UserController::class, 'restore']);
    Route::get('users/doctors', [UserController::class, 'doctors']);
    Route::get('users/pharmacies', [UserController::class, 'pharmacies']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::post('users/profile-picture', [UserController::class, 'uploadProfilePicture']);

    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::get('prescriptions/stats', [PrescriptionController::class, 'stats']);
    Route::get('prescriptions/order/{orderId}', [PrescriptionController::class, 'byOrder']);
    Route::post('prescriptions/upload', [PrescriptionController::class, 'upload']);
    Route::get('prescriptions/{prescriptionUpload}', [PrescriptionController::class, 'show']);
    Route::put('prescriptions/{prescriptionUpload}', [PrescriptionController::class, 'update']);
    Route::delete('prescriptions/{prescriptionUpload}', [PrescriptionController::class, 'destroy']);
    Route::post('prescriptions/{id}/restore', [PrescriptionController::class, 'restore']);
    Route::get('prescriptions/{prescriptionUpload}/image-url', [PrescriptionController::class, 'getImageUrlEndpoint']);

    Route::get('donations', [DonationController::class, 'index']);
    Route::get('donations/stats', [DonationController::class, 'stats']);
    Route::get('donations/my', [DonationController::class, 'myDonations']);
    Route::get('donations/verified', [DonationController::class, 'verified']);
    Route::put('donations/{donation}', [DonationController::class, 'update']);
    Route::delete('donations/{donation}', [DonationController::class, 'destroy']);
    Route::post('donations/{id}/restore', [DonationController::class, 'restore']);
    Route::get('donations/{donation}', [DonationController::class, 'show']);

    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
    Route::get('settings/permissions', [SettingController::class, 'getPermissions']);
    Route::post('settings/permissions', [SettingController::class, 'updatePermissions']);
    Route::get('settings/reports/{type}', [SettingController::class, 'getReports']);

    Route::get('active-ingredients', [ActiveIngredientController::class, 'index']);
    Route::post('active-ingredients', [ActiveIngredientController::class, 'store']);
    Route::get('active-ingredients/{activeIngredient}', [ActiveIngredientController::class, 'show']);
    Route::put('active-ingredients/{activeIngredient}', [ActiveIngredientController::class, 'update']);
    Route::delete('active-ingredients/{activeIngredient}', [ActiveIngredientController::class, 'destroy']);
    Route::post('active-ingredients/{id}/restore', [ActiveIngredientController::class, 'restore']);

    Route::get('therapeutic-classes', [TherapeuticClassController::class, 'index']);
    Route::post('therapeutic-classes', [TherapeuticClassController::class, 'store']);
    Route::get('therapeutic-classes/{therapeuticClass}', [TherapeuticClassController::class, 'show']);
    Route::put('therapeutic-classes/{therapeuticClass}', [TherapeuticClassController::class, 'update']);
    Route::delete('therapeutic-classes/{therapeuticClass}', [TherapeuticClassController::class, 'destroy']);
    Route::post('therapeutic-classes/{id}/restore', [TherapeuticClassController::class, 'restore']);
});
