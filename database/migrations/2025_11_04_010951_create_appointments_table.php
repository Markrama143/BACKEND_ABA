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

            // Connect appointment to user
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Patient & Pet Details
            $table->string('name');
            $table->integer('age');
            $table->string('sex');
            $table->string('animal_type');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();

            // Appointment Details
            $table->date('date');
            $table->string('time');

            $table->string('purpose');

            // Status
            $table->string('status')->default('Pending');

            $table->timestamps();

            // Indexes
            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
