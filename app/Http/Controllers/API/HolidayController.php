<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidayController extends Controller  // <--- You were missing this class line
{
    public function declareHoliday(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'name' => 'required|string'
        ]);

        $holidayDate = $request->date;
        Holiday::create(['date' => $holidayDate, 'name' => $request->name]);

        // Move existing appointments to the next day
        $appointments = Appointment::where('date', $holidayDate)->get();
        $nextDay = Carbon::parse($holidayDate)->addDay()->format('Y-m-d');

        foreach ($appointments as $app) {
            $app->date = $nextDay;
            $app->save();
        }

        return response()->json([
            'message' => "Holiday set. " . count($appointments) . " appointments moved to $nextDay"
        ]);
    }

    // Add this method to HolidayController class
    public function index()
    {
        $holidays = \App\Models\Holiday::all();
        return response()->json(['data' => $holidays]);
    }
}