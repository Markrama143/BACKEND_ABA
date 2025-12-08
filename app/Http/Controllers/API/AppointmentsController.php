<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentsController extends Controller
{
    /**
     * GET /api/appointments
     * Fetches all appointments for Admin, or only the user's appointments for a regular User.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // OPTIMIZATION: Define columns needed for efficient data retrieval
            $columns = [
                'id',
                'name',
                'sex',
                'age',
                'email',
                'phone_number',
                'animal_type',
                'date',
                'time',
                'status',
                'user_id',
                'created_at'
            ];

            $query = Appointment::select($columns);

            if ($user) {
                // FIX: Check for the 'admin' role. If admin, do NOT filter.
                if (isset($user->role) && $user->role !== 'admin') {
                    // Standard User: Filter by the user's foreign key reference.
                    // NOTE: This assumes appointments are linked by 'user_id' (or 'patient_id'). 
                    // Based on your Seeder, 'user_id' is the safer column to use here.
                    $query->where('user_id', $user->id);
                }
            } else {
                // Should technically return 401, but we return empty array for safety
                return response()->json(['data' => []], 200);
            }

            $appointments = $query
                ->orderBy('date', 'desc')
                ->orderBy('time', 'asc')
                ->get();

            return response()->json(['data' => $appointments], 200);
        } catch (Exception $e) {
            Log::error("Appointment Index Error: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Server Error: Could not fetch appointments.'], 500);
        }
    }

    /**
     * GET /api/appointments/availability
     */
    public function availability()
    {
        try {
            $data = DB::table('appointments')
                ->select('date', DB::raw('count(*) as total'))
                ->where('status', '!=', 'cancelled')
                ->groupBy('date')
                ->get();

            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/appointments
     * Updated: Books 1st Dose ONLY and suggests date for 2nd Dose.
     */
    public function store(Request $request)
    {
        // ... (store logic remains the same) ...
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'age'         => 'required|integer',
            'sex'         => 'required|string',
            'animal_type' => 'required|string',
            'date'        => 'required|date|after_or_equal:today',
            'time'        => 'required',
            'purpose'     => 'required|string',
            'guardian'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 1. Check Stock/Holiday for THIS appointment only
        $error = $this->checkStockAvailability($request->date);
        if ($error) return $error;

        // 2. Calculate the Suggested Date (Day 0 + 3 Days)
        $suggestedDate = null;
        if ($request->purpose === '1st Dose') {
            $suggestedDate = date('Y-m-d', strtotime($request->date . ' +3 days'));
        }

        try {
            $result = DB::transaction(function () use ($request, &$suggestedDate) {
                $userId = $request->user() ? $request->user()->id : null;
                $userEmail = $request->user() ? $request->user()->email : 'N/A';

                // Only create the single appointment requested
                return Appointment::create([
                    'user_id'      => $userId,
                    'guardian'     => $request->guardian,
                    'name'         => $request->name,
                    'age'          => $request->age,
                    'sex'          => $request->sex,
                    'animal_type'  => $request->animal_type,
                    'phone_number' => $request->input('phone_number', 'N/A'),
                    'email'        => $userEmail,
                    'date'         => $request->date,
                    'time'         => $request->time,
                    'purpose'      => $request->purpose,
                    'status'       => 'Pending',
                ]);
            });

            // 3. Create the Success Message
            $msg = 'Booking Successful!';
            if ($suggestedDate) {
                $msg .= " Please remember to book your 2nd dose for $suggestedDate.";
            }

            return response()->json(['success' => true, 'message' => $msg, 'data' => $result], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/appointments/{id}
     */
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string',
            'age'         => 'sometimes|integer',
            'date'        => 'sometimes|date|after_or_equal:today',
            'guardian'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Check stock if date changed
        if ($request->has('date') && $request->date != $appointment->date) {
            $error = $this->checkStockAvailability($request->date);
            if ($error) return $error;
        }

        try {
            $appointment->update($request->all());
            return response()->json(['success' => true, 'message' => 'Appointment Updated'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/appointments/{id}
     */
    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        try {
            $appointment->delete();
            return response()->json(['success' => true, 'message' => 'Appointment Cancelled'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to check stock AND holidays
     */
    private function checkStockAvailability($date)
    {
        // 1. Check Holiday FIRST
        // FIXED: Using $date variable directly
        $isHoliday = DB::table('holidays')->where('date', $date)->exists();
        if ($isHoliday) {
            return response()->json([
                'success' => false,
                'message' => 'The clinic is closed on this holiday.'
            ], 409);
        }

        // 2. Check Stock Limit
        // NOTE: We assume vaccine_stocks has a 'date' column and a 'quantity' column.
        $limit = DB::table('vaccine_stocks')->where('date', $date)->value('quantity');

        // If no stock is defined in the DB, assume 0 (No slots)
        if ($limit === null || $limit <= 0) {
            return response()->json([
                'success' => false,
                'message' => "No vaccine schedule/stock available for $date."
            ], 409);
        }

        // 3. Check how many people already booked
        $currentBookings = Appointment::where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($currentBookings >= $limit) {
            return response()->json([
                'success' => false,
                'message' => "Fully booked for $date! All $limit slots are taken."
            ], 409);
        }

        return null; // Available
    }
}
