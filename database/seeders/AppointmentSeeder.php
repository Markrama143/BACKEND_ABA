<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Faker\Factory as Faker;
use Carbon\Carbon; // Import Carbon for date handling

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('en_PH'); 
        $timeSlots = [
            '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', 
            '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM'
        ];
        $animalTypes = ['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster'];
        
        // Target users for assigning appointments
        $user = User::where('role', 'user')->first();
        $admin = User::where('role', 'admin')->first(); // Currently unused but good practice

        if (!$user) {
            echo "Warning: No 'user' found. Please run UserSeeder first.\n";
            return;
        }

        $userId = $user->id;
        $userEmail = $user->email;

        // --- Generate Appointments ---
        DB::table('appointments')->delete();

        // 1. Randomize the total number of appointments (e.g., between 30 and 49)
        $totalAppointmentsToCreate = $faker->numberBetween(30, 49); 

        // Define the target date range (November 1st to December 31st, 2025)
        $startDate = Carbon::create(2025, 11, 1);
        $endDate = Carbon::create(2025, 12, 31);
        
        // Track generated appointments
        $appointmentsCreated = 0;

        for ($i = 0; $i < $totalAppointmentsToCreate; $i++) {

            // --- Randomly choose a date within the Nov-Dec 2025 range ---
            $randomDate = $faker->dateTimeBetween($startDate, $endDate);
            $dbDate = $randomDate->format('Y-m-d');
            
            // Determine status based on whether the appointment is in the past (using current time)
            if ($randomDate < Carbon::now()) {
                // Past dates: mostly Completed or Confirmed
                $status = $faker->randomElement(['Confirmed', 'Completed']);
            } else {
                // Future dates: mostly Pending or Confirmed
                $status = $faker->randomElement(['Pending', 'Confirmed']);
            }

            $isUserAppointment = $faker->boolean(75); // 75% chance it belongs to the test user

            DB::table('appointments')->insert([
                // FIX: Include guardian details as per your migration
                'guardian'              => $faker->name(),
                'guardian_relationship' => $faker->randomElement(['Owner', 'Family', 'Friend']),
                
                'user_id'               => $isUserAppointment ? $userId : null,
                'name'                  => $faker->firstName() . ' ' . $faker->lastName(),
                'sex'                   => $faker->randomElement(['Male', 'Female']),
                'age'                   => $faker->numberBetween(1, 15),
                'email'                 => $isUserAppointment ? $userEmail : $faker->unique()->safeEmail(),
                'phone_number'          => $faker->numerify('09#########'),
                'animal_type'           => $faker->randomElement($animalTypes),
                
                'date'                  => $dbDate, // Correct column name used
                'time'                  => $faker->randomElement($timeSlots),
                'status'                => $status,
                'purpose'               => $faker->sentence(3),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
            $appointmentsCreated++;
        }

        echo "Seeded {$appointmentsCreated} appointments randomly between November 1st and December 31st, 2025.\n";
    }
}