<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\VaccineStock;
use App\Http\Controllers\API\VaccineStockController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class AnalyticsController extends Controller
{
    /**
     * Get basic dashboard statistics (Total Appts, Pending Count, Vaccine Stock).
     * Endpoint: /admin/stats
     */
    public function getAdminStats()
    {
        try {
            $totalAppointments = Appointment::count();
            $pendingRequests = Appointment::where('status', 'Pending')->count();

            // Note: VaccineStockController is instantiated here to access stock data
            $stockController = new VaccineStockController();
            $stockResponse = $stockController->index();

            $stockData = json_decode($stockResponse->content(), true);
            $totalVaccineStock = $stockData['total_stock'] ?? 0;

            if (!isset($totalVaccineStock)) {
                $totalVaccineStock = 0;
            }

            return response()->json([
                'success' => true,
                'totalAppointments' => $totalAppointments,
                'pendingRequests' => $pendingRequests,
                'vaccineStock' => $totalVaccineStock,
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin Stats Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'totalAppointments' => 0,
                'pendingRequests' => 0,
                'vaccineStock' => 0,
            ], 200);
        }
    }

    /**
     * Fetches raw appointment dates for frontend predictive analysis.
     * Endpoint: /admin/analytics/raw-appointments
     */
    public function getRawAppointments()
    {
        try {
            // FIX: Using the correct column name 'date' as defined in your migration
            $cutoffDate = Carbon::now()->subYears(3)->startOfDay();

            $rawAppointments = DB::table('appointments')
                ->select('date')
                ->where('date', '>=', $cutoffDate)
                ->get();

            // Return the raw data for client-side aggregation and prediction
            return response()->json([
                'success' => true,
                'message' => 'Raw appointment dates retrieved successfully.',
                'data' => $rawAppointments
            ], 200);
        } catch (Exception $e) {
            // Log the actual error for debugging the 500
            Log::error("Raw Appointments Fatal Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server failed to process the raw appointments query.'], 500);
        }
    }

    /**
     * Get the count of appointments grouped by animal type for charting (Descriptive Analysis).
     * Endpoint: /admin/analytics/animal-counts
     */
    public function getAnimalTypeAnalytics()
    {
        try {
            // OPTIMIZATION: Use the database to group and count directly.
            $animalCounts = DB::table('appointments')
                ->select('animal_type', DB::raw('COUNT(*) as total'))
                ->groupBy('animal_type')
                ->orderBy('total', 'desc')
                ->get();

            // Log the result to help verify data integrity
            Log::info("Animal Counts Retrieved: ", $animalCounts->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Animal appointment counts retrieved successfully.',
                'data' => $animalCounts
            ], 200);
        } catch (Exception $e) {
            Log::error("Animal Analytics Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Could not fetch animal analytics data.'], 500);
        }
    }

    /**
     * GET /api/admin/reports
     * Return a basic summary reports payload (stubbed).
     */
    public function getSummaryReports()
    {
        try {
            // Example report data: counts and simple aggregates
            $appointmentsByStatus = DB::table('appointments')
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            $recentAppointments = DB::table('appointments')
                ->orderBy('date', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'by_status' => $appointmentsByStatus,
                    'recent' => $recentAppointments,
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error("Summary Reports Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Could not fetch reports.'], 500);
        }
    }
}
