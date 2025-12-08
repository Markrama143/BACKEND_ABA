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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // --- GUARDIAN DETAILS ---
            $table->string('guardian')->nullable();
            $table->string('guardian_relationship')->nullable(); // <--- ADD THIS

            // PATIENT DETAILS
            $table->string('name');
            $table->integer('age');
            $table->string('sex');
            $table->string('animal_type');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();

            // APPOINTMENT DETAILS
            $table->date('date');
            $table->string('time');
            $table->string('purpose');
            $table->string('status')->default('Pending');

            $table->timestamps();
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};