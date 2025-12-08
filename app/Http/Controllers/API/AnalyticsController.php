<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment; // <-- CRITICAL: Required for Appointment model queries
use App\Models\VaccineStock; // <-- CRITICAL: Required for VaccineStock model queries
use App\Http\Controllers\API\VaccineStockController; // <-- CRITICAL: Required to call the stock logic
use Illuminate\Support\Facades\DB; // <-- CRITICAL: Required for DB::table and DB::raw
use Illuminate\Support\Facades\Log; // Required for Log::error
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
            // OPTIMIZATION: Use efficient aggregate queries
            $totalAppointments = Appointment::count();
            $pendingRequests = Appointment::where('status', 'Pending')->count();

            // Retrieve LIVE total stock sum from VaccineStockController
            $stockController = new VaccineStockController();
            $stockResponse = $stockController->index();

            // Decode the JSON response content to get the total_stock value
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
     * Get the count of appointments grouped by animal type for charting (DSA).
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
