<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Appointment;

class RecommendationController extends Controller
{
    public function getBestDay()
    {
        // Start looking from tomorrow
        $date = Carbon::tomorrow();
        $daysChecked = 0;

        // 1. Fetch all holidays from the DB so we can skip them
        $holidays = DB::table('holidays')->pluck('date')->toArray();

        // Loop through the next 14 days to find the best slot
        while ($daysChecked < 14) {
            $dateString = $date->format('Y-m-d');

            // 2. Skip Weekends (Saturday & Sunday)
            if ($date->isWeekend()) {
                $date->addDay();
                $daysChecked++;
                continue;
            }

            // 3. Skip Holidays (The Fix)
            if (in_array($dateString, $holidays)) {
                $date->addDay();
                $daysChecked++;
                continue;
            }

            // 4. Check Vaccine Stock
            $stock = DB::table('vaccine_stocks')->where('date', $dateString)->value('quantity');

            // If no stock set, skip (or assume 0)
            $limit = $stock ?? 0;

            if ($limit > 0) {
                // 5. Check Current Bookings
                $booked = Appointment::where('date', $dateString)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                // If slots are available, we found our winner!
                if ($booked < $limit) {
                    $slotsLeft = $limit - $booked;

                    return response()->json([
                        'success' => true,
                        'data' => [
                            'date' => $dateString,
                            'readable_date' => $date->format('l, M d'),
                            'slots_left' => $slotsLeft,
                            'traffic_level' => $slotsLeft > 5 ? 'Low' : 'High',
                        ]
                    ]);
                }
            }

            // Move to next day
            $date->addDay();
            $daysChecked++;
        }

        // Fallback if no days found
        return response()->json(['success' => false, 'message' => 'No slots available soon']);
    }
}