<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AppointmentsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\VaccineStockController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\HolidayController;

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// FIXED: Changed method name from 'getAvailability' to 'availability' to match Controller
Route::get('/appointments/availability', [AppointmentsController::class, 'availability']);

Route::get('/recommendation/best-day', [RecommendationController::class, 'getBestDay']);
Route::get('/avatars/{filename}', [ProfileController::class, 'getAvatar']);

Route::post('/vaccines/stock', [VaccineStockController::class, 'store']);
Route::get('/vaccines/stock', [VaccineStockController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/holidays', [HolidayController::class, 'declareHoliday']);
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::get('/user', [ProfileController::class, 'user']);
    Route::post('/user/avatar', [ProfileController::class, 'updateAvatar']);

    // Appointments Standard Resource Routes
    Route::get('/appointments', [AppointmentsController::class, 'index']);
    Route::patch('/appointments/{id}/status', [AppointmentsController::class, 'updateAppointmentStatus']);
    Route::post('/appointments', [AppointmentsController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentsController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentsController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentsController::class, 'destroy']);

    // Admin analytics routes
    Route::get('/admin/stats', [AnalyticsController::class, 'getAdminStats']);
    Route::get('/admin/analytics/animal-counts', [AnalyticsController::class, 'getAnimalTypeAnalytics']);
    Route::get('/admin/reports', [AnalyticsController::class, 'getSummaryReports']);
});