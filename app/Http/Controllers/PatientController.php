<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PatientController extends Controller
{
    /**
     * Display a listing of patients.
     * GET /api/patients
     */
    public function index()
    {
        try {
            $patients = Patient::with('appointments')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Patients retrieved successfully',
                'data' => $patients
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created patient.
     * POST /api/patients
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:patients,email',
                'sex' => 'required|string|in:Male,Female,Other',
                'age' => 'required|integer|min:0|max:150',
                'address' => 'required|string|max:255',
                'contact_number' => 'required|string|max:25'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient = Patient::create($validator->validated());
            $patient->load('appointments');

            return response()->json([
                'success' => true,
                'message' => 'Patient created successfully',
                'data' => $patient
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified patient.
     * GET /api/patients/{id}
     */
    public function show($id)
    {
        try {
            $patient = Patient::with('appointments')->find($id);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Patient retrieved successfully',
                'data' => $patient
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified patient.
     * PUT/PATCH /api/patients/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = Patient::find($id);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:patients,email,' . $id . ',patient_id',
                'sex' => 'sometimes|required|string|in:Male,Female,Other',
                'age' => 'sometimes|required|integer|min:0|max:150',
                'address' => 'sometimes|required|string|max:255',
                'contact_number' => 'sometimes|required|string|max:25'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient->update($validator->validated());
            $patient->load('appointments');

            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully',
                'data' => $patient
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified patient.
     * DELETE /api/patients/{id}
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::find($id);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search patients by name.
     * GET /api/patients/search/{name}
     */
    public function searchByName($name)
    {
        try {
            $patients = Patient::with('appointments')
                ->where('name', 'LIKE', '%' . $name . '%')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Patients search completed successfully',
                'data' => $patients
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patients by sex.
     * GET /api/patients/sex/{sex}
     */
    public function getBySex($sex)
    {
        try {
            $patients = Patient::with('appointments')
                ->where('sex', $sex)
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Patients with sex '{$sex}' retrieved successfully",
                'data' => $patients
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients by sex',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patients by age range.
     * GET /api/patients/age/{min}/{max}
     */
    public function getByAgeRange($min, $max)
    {
        try {
            $patients = Patient::with('appointments')
                ->whereBetween('age', [$min, $max])
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Patients aged between {$min} and {$max} retrieved successfully",
                'data' => $patients
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patients by age range',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get patient with appointments.
     * GET /api/patients/{id}/appointments
     */
    public function getPatientWithAppointments($id)
    {
        try {
            $patient = Patient::with(['appointments.doctor'])->find($id);

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Patient with appointments retrieved successfully',
                'data' => $patient
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve patient with appointments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
