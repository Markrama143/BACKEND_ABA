<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add 'status' column to appointments table if it doesn't exist
        if (Schema::hasTable('appointments') && !Schema::hasColumn('appointments', 'status')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Adding 'status' with a default value of 'Pending'
                // Optimization: Adding an index to 'status' speeds up filtering queries (e.g., "Show all Pending appointments")
                $table->string('status')->default('Pending')->after('time');
                $table->index('status');
            });
        }

        // 2. Add 'role' column to users table if it doesn't exist
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                // Adding 'role' to distinguish between 'admin' and 'user'
                $table->string('role')->default('user')->after('email');
            });
        }
    }

    public function down(): void
    {
        // Drop columns if rolling back migration
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'status')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
