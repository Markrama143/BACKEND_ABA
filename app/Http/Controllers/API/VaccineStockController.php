<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VaccineStock;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Essential for SUM aggregation
use Illuminate\Support\Facades\Log;
use Exception;

class VaccineStockController extends Controller
{
    // Update or create stock for a day
    public function store(Request $request)
    {
        try {
            // FIX 1: CHANGE VALIDATION KEY to 'quantity' 
            // OR use 'amount' if you prefer, but we must align with the frontend payload.
            // Since the frontend sends 'amount', we adjust the validation to match the frontend,
            // but map to the DB column 'quantity'.
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'amount' => 'required|integer|min:0', 
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            // 2. Save (update existing or create new)
            $stock = VaccineStock::updateOrCreate(
                ['date' => $request->date],
                ['quantity' => $request->amount] // Maps frontend 'amount' to DB 'quantity'
            );

            return response()->json([
                'success' => true,
                'message' => 'Vaccine stock updated successfully',
                'data' => $stock
            ]);
        } catch (Exception $e) {
             Log::error("Vaccine Stock Store Error: " . $e->getMessage());
             return response()->json(['success' => false, 'error' => 'Server error during stock update.'], 500);
        }
    }

    // Return stock list and TOTAL SUM for dashboard
    public function index()
    {
        try {
            // FIX 2: Calculate the database sum directly using the 'quantity' column
            $total = DB::table('vaccine_stocks')->sum('quantity');

            // Return the list for the restock page UI
            $stocks = VaccineStock::orderBy('date', 'asc')->get();
            
            return response()->json([
                'success' => true, 
                'total_stock' => $total, // CRITICAL: This key is read by the AnalyticsController
                'data' => $stocks // List of individual entries
            ]);
        } catch (Exception $e) {
            Log::error("Vaccine Stock Index Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Could not retrieve stock data.', 'total_stock' => 0], 500); 
        }
    }
}