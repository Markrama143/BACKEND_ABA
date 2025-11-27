<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();

            // Connect appointment to user and patient
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Store appointment details
            $table->date('appointment_date'); 
            $table->time('appointment_time');

            // Track appointment progress: pending → approved → completed or cancelled
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');

            // Reason for the visit (e.g., Vaccine, Checkup, Surgery)
            $table->string('purpose'); 

            $table->timestamps();
            
            // Make searching by date and status faster
            $table->index(['appointment_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};