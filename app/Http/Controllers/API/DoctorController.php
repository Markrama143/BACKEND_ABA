<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Doctor; // <-- Import the model
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Return doctors with ID and name only
        return Doctor::select('doctors_id', 'name')->get(); 
    }
}