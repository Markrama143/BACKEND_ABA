<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Essential for Transaction
use Exception;

class AppointmentsController extends Controller
{
    // Index method (unchanged)

    public function store(Request $request)
    {
        // 1. Validate input
        // Using original validation rules
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'age'         => 'required|integer', // specific type is better
            'sex'         => 'required|string',
            'animal_type' => 'required|string',
            'date'        => 'required|date|after:today', // Ensure date format
            'time'        => 'required',
            'purpose'     => 'required|string', // Added purpose as per good practice
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 2. Check availability
        $exists = Appointment::where('date', $request->date)
                             ->where('time', $request->time)
                             ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Slot already booked.'], 409);
        }

        try {
            // 3. Use DB transaction to keep related operations consistent
            $result = DB::transaction(function () use ($request) {
                
                // Get user ID if logged in, else null
                $userId = $request->user() ? $request->user()->id : null;
                $userEmail = $request->user() ? $request->user()->email : 'N/A';

                // Create the appointment record
                // Save the requested fields
                $appointment = Appointment::create([
                    'user_id'      => $userId, // Link to user account if exists
                    'name'         => $request->name,
                    'age'          => $request->age,
                    'sex'          => $request->sex,
                    'animal_type'  => $request->animal_type,
                    'phone_number' => $request->input('phone_number', 'N/A'),
                    'email'        => $userEmail,
                    'date'         => $request->date,
                    'time'         => $request->time,
                    'purpose'      => $request->purpose,
                    'status'       => 'pending',
                ]);

                // If an additional step fails, the transaction will roll back
                
                return $appointment;
            });

            // Transaction will commit automatically
            return response()->json(['success' => true, 'message' => 'Created', 'data' => $result], 201);

        } catch (Exception $e) {
            // Transaction will rollback on error
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Other methods
}