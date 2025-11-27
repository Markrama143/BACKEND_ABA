<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add status column to appointments if missing
        if (Schema::hasTable('appointments') && !Schema::hasColumn('appointments', 'status')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Status defaults to 'Pending'; index speeds up filtering
                $table->string('status')->default('Pending')->after('time');
                $table->index('status');
            });
        }

        // Add role column to users if missing
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                // Role defaults to 'user' to distinguish user types
                $table->string('role')->default('user')->after('email');
            });
        }
    }

    public function down(): void
    {
        // Rollback: drop added columns
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
