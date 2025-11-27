<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VaccineStock;
use Illuminate\Support\Facades\Validator;

class VaccineStockController extends Controller
{
    // Update or Create stock for a specific day
    public function store(Request $request)
    {
        // 1. Validate
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Save (Update if exists, Create if new)
        $stock = VaccineStock::updateOrCreate(
            ['date' => $request->date],
            ['quantity' => $request->amount]
        );

        return response()->json([
            'success' => true,
            'message' => 'Vaccine stock updated',
            'data' => $stock
        ]);
    }

    // Get stock list
    public function index()
    {
        $stocks = VaccineStock::orderBy('date', 'asc')->get();
        return response()->json(['success' => true, 'data' => $stocks]);
    }
}