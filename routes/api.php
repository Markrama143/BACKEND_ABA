<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AppointmentsController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\VaccineStockController;
use App\Http\Controllers\AuthController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/appointments/availability', [AppointmentsController::class, 'getAvailability']);
Route::get('/recommendation/best-day', [RecommendationController::class, 'getBestDay']);
// Public image route to avoid CORS blocking
Route::get('/avatars/{filename}', [ProfileController::class, 'getAvatar']);

Route::post('/vaccines/stock', [VaccineStockController::class, 'store']);
Route::get('/vaccines/stock', [VaccineStockController::class, 'index']);

// Protected routes (need auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ProfileController::class, 'user']);
    Route::post('/user/avatar', [ProfileController::class, 'updateAvatar']);
    Route::get('/appointments', [AppointmentsController::class, 'index']);
    Route::post('/appointments', [AppointmentsController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentsController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentsController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentsController::class, 'destroy']);
});