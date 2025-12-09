<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Holiday;
use App\Models\User;
use App\Notifications\AppointmentStatusChanged;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    /**
     * READ: Fetches the list of all clinic holidays, filtered to future/current dates.
     * Corresponds to: GET /api/holidays
     */
    public function index()
    {
        try {
            // Fetch only holidays that are today or in the future
            $holidays = Holiday::where('date', '>=', Carbon::today()->toDateString())
                ->orderBy('date', 'asc')
                ->get(['id', 'date', 'name']); // Select specific columns

            return response()->json([
                'success' => true,
                'data' => $holidays
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to fetch holidays: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not retrieve holiday list.'], 500);
        }
    }

    // --- CREATE Operation (Your existing logic, enhanced with validation) ---

    /**
     * POST /api/holidays
     * Declares a holiday and moves appointments to the NEXT WORKING DAY.
     */
    public function declareHoliday(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d', 'unique:holidays,date', 'after_or_equal:today'],
            'name' => ['required', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for holiday creation.',
                'errors' => $validator->errors()
            ], 422);
        }

        $holidayDate = $request->date;
        $holidayName = $request->name;

        // 2. Save the new Holiday
        $holiday = Holiday::create([
            'date' => $holidayDate,
            'name' => $holidayName
        ]);

        // --- Appointment Rescheduling Logic (Your existing robust logic) ---

        // Find appointments affected by THIS holiday
        $appointments = Appointment::where('date', $holidayDate)
            ->where('status', '!=', 'cancelled')
            ->get();

        // Find the next valid WORKING day (Skip Holidays AND Weekends)
        $allHolidays = Holiday::pluck('date')->toArray();
        $nextDate = Carbon::parse($holidayDate);

        do {
            $nextDate->addDay();
        } while ($nextDate->isWeekend() || in_array($nextDate->format('Y-m-d'), $allHolidays));

        $finalDateString = $nextDate->format('Y-m-d');
        $count = 0;

        // Move all appointments and notify
        foreach ($appointments as $app) {
            $app->date = $finalDateString;
            $app->save();

            $user = User::find($app->user_id);
            if ($user) {
                $message = "Your appointment has been rescheduled to $finalDateString due to $holidayName Holiday.";
                $user->notify(new AppointmentStatusChanged($message, $app->id));
            }
            $count++;
        }
        // --- End of Rescheduling Logic ---

        return response()->json([
            'success' => true,
            'message' => "Holiday set. $count appointments moved to $finalDateString.",
            'data' => $holiday
        ], 201);
    }

    // --- NEW UPDATE Operation ---

    /**
     * UPDATE: Updates a holiday's details. Does NOT trigger mass rescheduling
     * Corresponds to: PUT /api/holidays/{id}
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'success' => false,
                'message' => 'Holiday not found.'
            ], 404);
        }

        // 1. Validation (Unique constraint must ignore the current holiday's ID)
        $validator = Validator::make($request->all(), [
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('holidays')->ignore($holiday->id), // Ignore current holiday
                'after_or_equal:today'
            ],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed for holiday update.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 2. Update and save
            $holiday->date = $request->date;
            $holiday->name = $request->name;
            $holiday->save();

            // NOTE: Changing the date might require rescheduling checks, 
            // but for a simple PUT/PATCH, we assume the admin handles this manually 
            // or we add a separate endpoint for date changes that triggers the logic.
            // For now, we only update the record.

            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully.',
                'data' => $holiday
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to update holiday ID $id: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // --- DELETE Operation ---

    /**
     * DELETE: Removes a holiday by its ID.
     * Corresponds to: DELETE /api/holidays/{id}
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $holiday = Holiday::find($id);

            if (!$holiday) {
                return response()->json([
                    'success' => false,
                    'message' => 'Holiday not found.'
                ], 404);
            }

            $holiday->delete();

            // NOTE: Deleting a holiday might leave appointments stranded if they were
            // previously moved to this date. A background check might be useful here, 
            // but for core CRUD, simply deleting is sufficient.

            return response()->json([
                'success' => true,
                'message' => 'Holiday deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            Log::error("Failed to delete holiday ID $id: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the holiday.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
