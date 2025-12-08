<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Faker\Factory as Faker;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('en_PH'); // Use Philippine locale for names/data
        $timeSlots = [
            '08:00 AM',
            '09:00 AM',
            '10:00 AM',
            '01:00 PM',
            '02:00 PM',
            '03:00 PM'
        ];
        $animalTypes = ['Dog', 'Cat', 'Bird', 'Rabbit', 'Hamster'];
        $statuses = ['Pending', 'Confirmed', 'Completed'];

        // Get one default user's email to assign appointments to (for testing user dashboard)
        $user = User::where('role', 'user')->first();
        $admin = User::where('role', 'admin')->first();

        // Check if users exist for a robust seeding process
        if (!$user) {
            echo "Warning: No 'user' found. Please run UserSeeder first.\n";
            return;
        }

        $userId = $user->id;
        $userEmail = $user->email;

        // --- Generate Appointments ---
        DB::table('appointments')->delete();

        // Target 50 total appointments
        for ($i = 0; $i < 50; $i++) {

            // --- Randomly choose a date between November 1st and December 31st, 2025 ---
            $startDate = '2025-11-01';
            $endDate = '2025-12-31';

            // Generates a random DateTime object within the range
            $randomDate = $faker->dateTimeBetween($startDate, $endDate);

            // Determine status based on whether the appointment is in the past
            if ($randomDate < now()) {
                // Past dates are mostly Completed or Confirmed
                $status = $faker->randomElement(['Confirmed', 'Completed']);
            } else {
                // Future dates are mostly Pending or Confirmed
                $status = $faker->randomElement(['Pending', 'Confirmed']);
            }

            // Format date and time for the database columns
            $dbDate = $randomDate->format('Y-m-d');

            $isUserAppointment = $faker->boolean(75); // 75% chance it belongs to the test user

            DB::table('appointments')->insert([
                'name'              => $isUserAppointment ? $user->name : $faker->firstName() . ' ' . $faker->lastName(),
                'sex'               => $faker->randomElement(['Male', 'Female']),
                'age'               => $faker->numberBetween(1, 15),
                'email'             => $isUserAppointment ? $userEmail : $faker->unique()->safeEmail(),
                'phone_number'      => $faker->numeric('09#########'),
                'animal_type'       => $faker->randomElement($animalTypes),
                'date'              => $dbDate,
                'time'              => $faker->randomElement($timeSlots),
                'status'            => $status,
                'purpose'           => $faker->sentence(3),
                // FIX: Assuming the column is 'user_id' for the foreign key reference
                'user_id'           => $isUserAppointment ? $userId : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        echo "Seeded 50 appointments randomly between November 1st and December 31st, 2025.\n";
    }
}
