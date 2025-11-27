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
        // 1. Setup Timezone
        date_default_timezone_set('Asia/Manila');

        $now = Carbon::now();
        $startDate = Carbon::today();

        // 2. TIME CONSTRAINT: If past 5:00 PM, skip today and start looking from tomorrow
        if ($now->hour >= 17) {
            $startDate->addDay();
        }

        // Search the next 10 days (extended slightly to ensure we find a weekday if today is Friday)
        $endDate = $startDate->copy()->addDays(10);

        $bestOption = null;
        $mostFreeSlots = -1;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {

            // --- NEW: WEEKEND EXCLUSION ---
            // If the day is Saturday or Sunday, skip it immediately.
            if ($date->isWeekend()) {
                continue;
            }

            $dateStr = $date->format('Y-m-d');

            // 3. Get Capacity (Stock or Default 15)
            $stock = VaccineStock::where('date', $dateStr)->first();
            $capacity = $stock ? $stock->quantity : 15;

            // 4. Get Actual Bookings
            $booked = Appointment::where('date', $dateStr)->count();

            // 5. Calculate Free Slots
            $freeSlots = $capacity - $booked;

            // 6. LOGIC: Find the day with the MOST free slots
            if ($freeSlots > 0) {
                if ($freeSlots > $mostFreeSlots) {
                    $mostFreeSlots = $freeSlots;

                    // Calculate Traffic Level
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