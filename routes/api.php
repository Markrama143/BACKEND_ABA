<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AppointmentsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\VaccineStockController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AnalyticsController; // <--- NEW: Import AnalyticsController

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/appointments/availability', [AppointmentsController::class, 'availability']);

Route::get('/recommendation/best-day', [RecommendationController::class, 'getBestDay']);
Route::get('/avatars/{filename}', [ProfileController::class, 'getAvatar']);

Route::post('/vaccines/stock', [VaccineStockController::class, 'store']);
Route::get('/vaccines/stock', [VaccineStockController::class, 'index']);

// --- Protected Routes (Need Login) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ProfileController::class, 'user']);
    Route::post('/user/avatar', [ProfileController::class, 'updateAvatar']);

    // Appointments Standard Resource Routes
    Route::get('/appointments', [AppointmentsController::class, 'index']);
    Route::post('/appointments', [AppointmentsController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentsController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentsController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentsController::class, 'destroy']);

    // --- ADMIN / ANALYTICS ROUTES ---
    // Dashboard Stats (Used for Admin Dashboard home screen)
    Route::get('/admin/stats', [AnalyticsController::class, 'getAdminStats']);

    // DSA - Appointment Count by Animal Type
    Route::get('/admin/analytics/animal-counts', [AnalyticsController::class, 'getAnimalTypeAnalytics']);

    // Reports endpoint
    Route::get('/admin/reports', [AnalyticsController::class, 'getSummaryReports']);
    // --- END ADMIN / ANALYTICS ROUTES ---
});