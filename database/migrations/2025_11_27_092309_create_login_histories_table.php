<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            // Foreign key to user
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Security info: IP and user agent
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable(); // Browser and device info
            
            // Timestamp of login
            $table->timestamp('login_at'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};