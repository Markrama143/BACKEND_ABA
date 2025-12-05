<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Add this line to run your specific file
        $this->call([
            AdminUserSeeder::class,
        ]);
        $this->call([
            UserSeeder::class,
        ]);
        $this->call([
            AppointmentSeeder::class,
        ]);
    
    
    }
    
}
