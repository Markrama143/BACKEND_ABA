<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('vaccine_stocks', function (Blueprint $table) {
        $table->id();
        $table->date('date')->unique(); // Ensures one record per day
        $table->integer('quantity');    // The stock amount
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccine_stocks');
    }
};