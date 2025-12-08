<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\VaccineStock; 
use Illuminate\Support\Facades\DB;
use Exception;
// FIX: Need to import the VaccineStockController to call its index method
use App\Http\Controllers\API\VaccineStockController; 
// Need to ensure Response class is accessible for manual calls
use Illuminate\Http\JsonResponse; 
use Illuminate\Support\Facades\Log;


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
            $totalAppointments = DB::table('appointments')->count();
            $pendingRequests = DB::table('appointments')->where('status', 'Pending')->count();
            
            // FIX: Retrieve LIVE total stock sum from VaccineStockController
            $stockController = new VaccineStockController();
            $stockResponse = $stockController->index();
            
            // Decode the JSON response content to get the total_stock value
            $stockData = json_decode($stockResponse->content(), true);
            $totalVaccineStock = $stockData['total_stock'] ?? 0;
            
            // Fallback safety check: if the main stock key is missing, use 0
            if (!isset($totalVaccineStock)) {
                $totalVaccineStock = 0;
            }


            return response()->json([
                'success' => true,
                'totalAppointments' => $totalAppointments,
                'pendingRequests' => $pendingRequests,
                'vaccineStock' => $totalVaccineStock, // FIX: Use the calculated live value
            ], 200);

        } catch (Exception $e) {
            Log::error("Admin Stats Error: " . $e->getMessage()); 
            // Returning 200 with 0 values to keep the frontend running smoothly even if DB fails
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