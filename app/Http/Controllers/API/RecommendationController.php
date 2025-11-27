<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\VaccineStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    public function getBestDay()
    {
        // Set timezone
        date_default_timezone_set('Asia/Manila');

        $now = Carbon::now();
        $startDate = Carbon::today();

        // If after 5 PM, start from tomorrow
        if ($now->hour >= 17) {
            $startDate->addDay();
        }

        // Search the next 10 days (extended slightly to ensure we find a weekday if today is Friday)
        $endDate = $startDate->copy()->addDays(10);

        $bestOption = null;
        $mostFreeSlots = -1;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');

            // Get capacity (stock or default 15)
            $stock = VaccineStock::where('date', $dateStr)->first();
            $capacity = $stock ? $stock->quantity : 15;

            // Count booked appointments
            $booked = Appointment::where('date', $dateStr)->count();

            // Compute free slots
            $freeSlots = $capacity - $booked;

            // Pick day with most free slots
            if ($freeSlots > 0) {
                if ($freeSlots > $mostFreeSlots) {
                    $mostFreeSlots = $freeSlots;

                    // Calculate traffic level
                    $percentage = ($booked / $capacity) * 100;
                    $traffic = $percentage < 30 ? 'Low' : ($percentage < 70 ? 'Medium' : 'High');

                    $bestOption = [
                        'date' => $dateStr,
                        'readable_date' => $date->format('l, M d'),
                        'traffic_level' => $traffic,
                        'slots_left' => $freeSlots
                    ];
                }
            }
        }

        if ($bestOption) {
            return response()->json([
                'success' => true,
                'data' => $bestOption,
                'message' => 'Optimal appointment day found.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No optimal days found in the coming week.'
        ]);
    }
}