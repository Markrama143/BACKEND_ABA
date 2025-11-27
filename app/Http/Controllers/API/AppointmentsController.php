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
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // OPTIMIZATION: Defining the exact columns needed prevents fetching unnecessary data.
                // This aligns with the "Retrieve as little data as possible" principle.
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
                    'created_at'
                ];

                if (isset($user->role) && $user->role === 'admin') {
                    // ADMIN: Fetch ALL appointments sorted by newest first
                    $appointments = Appointment::select($columns)
                        ->orderBy('created_at', 'desc')
                        ->get();
                } else {
                    // USER: Fetch ONLY their appointments
                    // Ensure 'email' column is indexed in your database for faster lookup
                    $appointments = Appointment::select($columns)
                        ->where('email', $user->email)
                        ->orderBy('created_at', 'desc')
                        ->get();
                }
            } else {
                $appointments = [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Appointments retrieved successfully',
                'data' => $appointments
            ], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'age' => 'required',
                'sex' => 'required|string',
                'animal_type' => 'required|string',
                'date' => 'required',
                'time' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }

            // OPTIMIZATION: 'exists()' stops searching as soon as a match is found.
            // Ensure a composite index on [date, time] exists in DB for max performance.
            $exists = Appointment::where('date', $request->date)->where('time', $request->time)->exists();

            if ($exists) {
                return response()->json(['success' => false, 'message' => 'Slot already booked.'], 409);
            }

            $data = $validator->validated();
            $data['phone_number'] = $request->input('phone_number', 'N/A');
            $data['email'] = $request->user() ? $request->user()->email : 'N/A';
            $data['patient_id'] = null;
            $data['status'] = 'Pending'; // Safe to use now

            $appointment = Appointment::create($data);

            return response()->json(['success' => true, 'message' => 'Created', 'data' => $appointment], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $appointment = Appointment::find($id);
            if (!$appointment) return response()->json(['success' => false, 'message' => 'Not found'], 404);
            return response()->json(['success' => true, 'data' => $appointment], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getAvailability()
    {
        try {
            // OPTIMIZATION: Using database aggregation (count, groupBy) is much faster 
            // than fetching all rows and counting them in PHP.
            $counts = DB::table('appointments')
                ->select('date', DB::raw('count(*) as total'))
                ->groupBy('date')
                ->get();
            return response()->json(['success' => true, 'data' => $counts], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $appointment = Appointment::find($id);
            if (!$appointment) return response()->json(['success' => false, 'message' => 'Not found'], 404);
            $appointment->delete();
            return response()->json(['success' => true, 'message' => 'Deleted'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
