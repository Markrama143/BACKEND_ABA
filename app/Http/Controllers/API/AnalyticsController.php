<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyticsController extends Controller
{
    /**
     * Get the count of appointments grouped by animal type for charting (DSA).
     * * Endpoint: /admin/analytics/animal-counts
     * @return \Illuminate\Http\JsonResponse
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
     * Get basic dashboard statistics (e.g., total appointments, pending count).
     * * Endpoint: /admin/stats
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAdminStats()
    {
        try {
            // OPTIMIZATION: Use highly efficient count and aggregate queries.
            $totalAppointments = DB::table('appointments')->count();
            $pendingRequests = DB::table('appointments')->where('status', 'Pending')->count();
            
            // NOTE: Vaccine stock relies on a separate 'stock' or 'inventory' table.
            // Mocking for now, assuming a stock table exists.
            $vaccineStock = 42; 

            return response()->json([
                'success' => true,
                'totalAppointments' => $totalAppointments,
                'pendingRequests' => $pendingRequests,
                'vaccineStock' => $vaccineStock,
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin Stats Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Could not fetch dashboard statistics.'], 500);
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