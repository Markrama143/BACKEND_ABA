<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AppointmentsController;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\VaccineStockController;
use App\Http\Controllers\LoginController;

// ===================================
// PUBLIC ROUTES
// ===================================
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::get('/appointments/availability', [AppointmentsController::class, 'getAvailability']);
Route::get('/recommendation/best-day', [RecommendationController::class, 'getBestDay']);
// --- NEW: PUBLIC IMAGE ROUTE (Fixes CORS) ---
// This allows the browser to fetch the image without blocking it
Route::get('/avatars/{filename}', [ProfileController::class, 'getAvatar']);

Route::post('/vaccines/stock', [VaccineStockController::class, 'store']);
Route::get('/vaccines/stock', [VaccineStockController::class, 'index']);

// ===================================
// PROTECTED ROUTES
// ===================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ProfileController::class, 'user']);
    Route::post('/user/avatar', [ProfileController::class, 'updateAvatar']);
    Route::get('/appointments', [AppointmentsController::class, 'index']);
    Route::post('/appointments', [AppointmentsController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentsController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentsController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentsController::class, 'destroy']);
});