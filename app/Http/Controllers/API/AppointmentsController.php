<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\VaccineStock; 
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
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $columns = [
                'id',
                'name',
                'guardian',
                'sex',
                'age',
                'email',
                'phone_number',
                'animal_type',
                'date',
                'time',
                'status',
                'user_id',
                'created_at',
                'purpose'
            ];

            $query = Appointment::select($columns);

            if ($user) {
                if (isset($user->role) && $user->role !== 'admin') {
                    $query->where('user_id', $user->id);
                }
            } else {
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
     * Implements stock deduction upon booking.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string',
            'age'           => 'required|integer',
            'sex'           => 'required|string',
            'animal_type'   => 'required|string',
            'date'          => 'required|date|after_or_equal:today',
            'time'          => 'required',
            'purpose'       => 'required|string',
            'guardian'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // 1. Check Stock/Holiday BEFORE starting transaction
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

                // Fetch the stock entry for the specific date inside the transaction
                $stock = VaccineStock::where('date', $request->date)->lockForUpdate()->first(); // lock row

                if (!$stock || $stock->quantity <= 0) {
                    // Although checked before, this ensures atomic failure if stock changed mid-transaction
                    throw new Exception("Stock not available for transaction.");
                }

                // Deduct 1 unit from the quantity
                $stock->quantity -= 1;
                $stock->save();
                
                // Create the appointment record
                return Appointment::create([
                    'user_id'       => $userId,
                    'guardian'      => $request->guardian,
                    'name'          => $request->name,
                    'age'           => $request->age,
                    'sex'           => $request->sex,
                    'animal_type'   => $request->animal_type,
                    'phone_number'  => $request->input('phone_number', 'N/A'),
                    'email'         => $userEmail,
                    'date'          => $request->date,
                    'time'          => $request->time,
                    'purpose'       => $request->purpose,
                    'status'        => 'Pending',
                ]);
            });

            // 3. Create the Success Message
            $msg = 'Booking Successful! One dose deducted from stock.';
            if ($suggestedDate) {
                $msg .= " Please remember to book your 2nd dose for $suggestedDate.";
            }

            return response()->json(['success' => true, 'message' => $msg, 'data' => $result], 201);
        } catch (Exception $e) {
            Log::error("Appointment Store/Stock Deduction Error: " . $e->getMessage());
            // If any part of the transaction fails (including stock deduction), it rolls back.
            return response()->json(['success' => false, 'error' => $e->getMessage(), 'message' => 'Booking failed due to server error or stock issue.'], 500);
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
            'name'          => 'sometimes|string',
            'age'           => 'sometimes|integer',
            'date'          => 'sometimes|date|after_or_equal:today',
            'guardian'      => 'nullable|string',
            // Add other updatable fields here
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Logic for re-checking stock if the date is changed
        if ($request->has('date') && $request->date != $appointment->date) {
            // Check stock availability for the NEW date
            $error = $this->checkStockAvailability($request->date);
            if ($error) return $error;
            
            // NOTE: For a date change, you would also need to refund stock for the OLD date
            // and deduct for the NEW date, which is complex. For simple PUT, we assume 
            // the check above is sufficient, or this endpoint is primarily for metadata updates.
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
            // NOTE: You might want to refund stock here if the appointment was not cancelled before deletion
            $appointment->delete();
            return response()->json(['success' => true, 'message' => 'Appointment Cancelled'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // --- METHOD TO UPDATE APPOINTMENT STATUS ---
    public function updateAppointmentStatus(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['success' => false, 'message' => 'Appointment not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Pending,Confirmed,Completed,Cancelled',
        ]);

        if ($validator->fails()) {
            Log::warning("Status Update Validation Failed for ID {$id}: " . json_encode($validator->errors()));
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $oldStatus = $appointment->status;
        $newStatus = $request->status;

        try {
            // Check if status is changed to Cancelled, and if so, potentially refund stock
            if ($oldStatus !== 'Cancelled' && $newStatus === 'Cancelled') {
                // Find the stock entry and increase quantity by 1
                $stock = VaccineStock::where('date', $appointment->date)->first();

                if ($stock) {
                    $stock->quantity += 1;
                    $stock->save();
                    Log::info("Stock refunded for cancelled appointment {$id}.");
                }
            }

            // Check if status is changed FROM Cancelled (means stock must be re-checked/deducted)
            if ($oldStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
                // Check if stock is still available (current date stock > current bookings + 1)
                $error = $this->checkStockAvailability($appointment->date);
                if ($error) return $error;

                // If stock is available, deduct it again
                $stock = VaccineStock::where('date', $appointment->date)->lockForUpdate()->first();
                if ($stock && $stock->quantity > 0) {
                    $stock->quantity -= 1;
                    $stock->save();
                    Log::info("Stock deducted for re-activated appointment {$id}.");
                } else {
                    // This should not happen if checkStockAvailability passed, but as a final guard:
                    return response()->json(['success' => false, 'message' => 'Stock replenishment failed. Cannot move from Cancelled.'], 409);
                }
            }


            // Final Update
            $appointment->status = $newStatus;
            $appointment->save();

            return response()->json([
                'success' => true,
                'message' => 'Status updated to ' . $newStatus,
                'data' => $appointment
            ], 200);
        } catch (Exception $e) {
            Log::error("Status Update Error for ID {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Helper to check stock AND holidays
     */
    private function checkStockAvailability($date)
    {
        // 1. Check Holiday FIRST
        $isHoliday = DB::table('holidays')->where('date', $date)->exists();
        if ($isHoliday) {
            return response()->json([
                'success' => false,
                'message' => 'The clinic is closed on this holiday.'
            ], 409);
        }

        // 2. Check Stock Limit
        $limit = DB::table('vaccine_stocks')->where('date', $date)->value('quantity');

        if ($limit === null || $limit <= 0) {
            return response()->json([
                'success' => false,
                'message' => "No vaccine schedule/stock available for $date."
            ], 409);
        }

        // 3. Check how many people already booked (Excluding cancelled appointments)
        $currentBookings = Appointment::where('date', $date)
            ->where('status', '!=', 'Cancelled') // Capitalized for consistency with model/status update
            ->count();

        // Check if there is enough stock for one more appointment (Total available slots = $limit)
        if ($currentBookings >= $limit) {
            return response()->json([
                'success' => false,
                'message' => "Fully booked for $date! All $limit slots are taken."
            ], 409);
        }

        return null; // Available
    }
}