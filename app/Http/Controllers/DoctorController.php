<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors.
     * GET /api/doctors
     */
    public function index()
    {
        try {
            $doctors = Doctor::with('appointments')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Doctors retrieved successfully',
                'data' => $doctors
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created doctor.
     * POST /api/doctors
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'contact_number' => 'required|string|max:25'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $doctor = Doctor::create($validator->validated());
            $doctor->load('appointments');

            return response()->json([
                'success' => true,
                'message' => 'Doctor created successfully',
                'data' => $doctor
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified doctor.
     * GET /api/doctors/{id}
     */
    public function show($id)
    {
        try {
            $doctor = Doctor::with('appointments')->find($id);

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Doctor retrieved successfully',
                'data' => $doctor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified doctor.
     * PUT/PATCH /api/doctors/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'contact_number' => 'sometimes|required|string|max:25'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $doctor->update($validator->validated());
            $doctor->load('appointments');

            return response()->json([
                'success' => true,
                'message' => 'Doctor updated successfully',
                'data' => $doctor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified doctor.
     * DELETE /api/doctors/{id}
     */
    public function destroy($id)
    {
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            $doctor->delete();

            return response()->json([
                'success' => true,
                'message' => 'Doctor deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search doctors by name.
     * GET /api/doctors/search/{name}
     */
    public function searchByName($name)
    {
        try {
            $doctors = Doctor::with('appointments')
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Doctors search completed successfully',
                'data' => $doctors
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search doctors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get doctor with appointments.
     * GET /api/doctors/{id}/appointments
     */
    public function getDoctorWithAppointments($id)
    {
        try {
            $doctor = Doctor::with(['appointments.patient'])->find($id);

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Doctor with appointments retrieved successfully',
                'data' => $doctor
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve doctor with appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
