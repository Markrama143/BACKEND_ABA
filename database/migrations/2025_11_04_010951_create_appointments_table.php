<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); // Standard primary key

            // Appointment Details
            $table->string('name');
            $table->string('sex');
            $table->integer('age');
            $table->string('email')->nullable(); // Made nullable just in case
            $table->string('phone_number')->nullable();
            $table->string('animal_type')->nullable();

            // --- ADDED: Date and Time (Required for your App) ---
            $table->string('date')->nullable();
            $table->string('time')->nullable();

            // --- THE FIX: Create the column BEFORE the foreign key ---
            // We make it nullable so you can create an appointment even if patient_id is missing temporarily
            $table->unsignedBigInteger('patient_id')->nullable();

            $table->timestamps();

            // --- FOREIGN KEY CONSTRAINT ---
            // IMPORTANT: This assumes your 'patients' table has a column named 'patient_id'.
            // If your patients table uses the standard '$table->id()', change 'patient_id' below to 'id'.
            $table->foreign('patient_id')->references('patient_id')->on('patients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
