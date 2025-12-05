<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class AppointmentsController extends Controller
{
    /**
     * GET /api/appointments
     * UPDATED: Now handles Admin vs User logic.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['data' => []], 200);

            // 1. CHECK ROLE
            if (isset($user->role) && $user->role === 'admin') {
                // ADMIN: Fetch ALL appointments + User details
                $appointments = Appointment::with('user') // Eager load owner info
                    ->orderBy('date', 'desc')
                    ->orderBy('time', 'asc')
                    ->get();
            } else {
                // USER: Fetch only THEIR appointments
                $appointments = Appointment::where('user_id', $user->id)
                    ->orderBy('date', 'desc')
                    ->orderBy('time', 'asc')
                    ->get();
            }

            return response()->json(['data' => $appointments], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/admin/stats
     * NEW: Fetch statistics for the Dashboard Chart/Cards.
     */
    public function dashboardStats()
    {
        try {
            // 1. Count by Month (e.g., October: 15, November: 20)
            $monthlyStats = Appointment::select(
                    DB::raw('MONTHNAME(date) as month'), 
                    DB::raw('count(*) as total')
                )
                ->groupBy(DB::raw('MONTHNAME(date)'))
                ->orderBy(DB::raw('MIN(date)')) // Keep chronological order
                ->get();

            // 2. Count by Status (e.g., Pending: 5, Approved: 10)
            $statusStats = Appointment::select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // 3. Total Count
            $total = Appointment::count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_appointments' => $total,
                    'by_month' => $monthlyStats,
                    'by_status' => $statusStats
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/appointments/availability
     */
    public function availability()
    {
        try {
            $data = Appointment::select('date', DB::raw('count(*) as total'))
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
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'age'         => 'required|integer',
            'sex'         => 'required|string',
            'animal_type' => 'required|string',
            'date'        => 'required|date|after:today',
            'time'        => 'required',
            'purpose'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Check Stock
        $error = $this->checkStockAvailability($request->date);
        if ($error) return $error;

        try {
            $result = DB::transaction(function () use ($request) {
                $userId = $request->user() ? $request->user()->id : null;
                $userEmail = $request->user() ? $request->user()->email : 'N/A';

                return Appointment::create([
                    'user_id'      => $userId,
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

            return response()->json(['success' => true, 'message' => 'Booking Successful', 'data' => $result], 201);
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

        // Validate
        $validator = Validator::make($request->all(), [
            'name'        => 'sometimes|string',
            'age'         => 'sometimes|integer',
            'date'        => 'sometimes|date|after:today',
            // Add other fields as needed
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // If Date is changing, we must check stock for the NEW date
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
     * Helper to check stock
     */
    private function checkStockAvailability($date)
    {
        // If your vaccine_stocks table logic is needed, keep this here.
        // Make sure the table 'vaccine_stocks' actually exists.
        $limit = DB::table('vaccine_stocks')->where('date', $date)->value('quantity') ?? 0;

        // Note: If you have NO stock record, this assumes 0 stock. 
        // If you want default stock, change ?? 0 to ?? 50 (or whatever default is).
        
        if ($limit <= 0) {
             // You might want to allow booking if there is no stock record (limit=0)
             // or restrict it. Adjust logic as needed.
             // For now, I'll assume if no record exists, it means 0 stock available.
            return response()->json(['success' => false, 'message' => 'No vaccine stock available for this date.'], 409);
        }

        $currentBookings = Appointment::where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();

        if ($currentBookings >= $limit) {
            return response()->json(['success' => false, 'message' => "Fully booked! All $limit slots are taken."], 409);
        }

        return null; // No error
    }
}