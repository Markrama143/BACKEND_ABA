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
use App\Http\Controllers\API\AuditLogsController;
use App\Http\Controllers\Api\NotificationController;

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/appointments/availability', [AppointmentsController::class, 'availability']);

Route::get('/recommendation/best-day', [RecommendationController::class, 'getBestDay']);
Route::get('/avatars/{filename}', [ProfileController::class, 'getAvatar']);

Route::post('/vaccines/stock', [VaccineStockController::class, 'store']);
Route::get('/vaccines/stock', [VaccineStockController::class, 'index']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/holidays', [HolidayController::class, 'declareHoliday']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);
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
    
    // NEW ROUTE for Frontend Prediction Data
    Route::get('/admin/analytics/raw-appointments', [AnalyticsController::class, 'getRawAppointments']); 
    
    // Audit Logs Routes
    Route::get('/audit-logs', [AuditLogsController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditLogsController::class, 'show']);
});