<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Trigger 1: Log when new appointment is created
        DB::unprepared('
            CREATE TRIGGER tr_audit_appointments_insert
            AFTER INSERT ON appointments
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (user_id, action, table_name, details, created_at, updated_at)
                VALUES (
                    NEW.user_id, 
                    "CREATE", 
                    "appointments", 
                    CONCAT("New appointment booked for ", NEW.appointment_date, " at ", NEW.appointment_time), 
                    NOW(), 
                    NOW()
                );
            END
        ');

        // Trigger 2: Log when appointment status changes
        DB::unprepared('
            CREATE TRIGGER tr_audit_appointments_update
            AFTER UPDATE ON appointments
            FOR EACH ROW
            BEGIN
                -- Only log if the status actually changed (e.g., pending -> approved)
                IF OLD.status != NEW.status THEN
                    INSERT INTO audit_logs (user_id, action, table_name, details, created_at, updated_at)
                    VALUES (
                        NEW.user_id, 
                        "UPDATE", 
                        "appointments", 
                        CONCAT("Appointment status changed from ", OLD.status, " to ", NEW.status), 
                        NOW(), 
                        NOW()
                    );
                END IF;
            END
        ');

        // Trigger 3: Log when appointment is deleted
        DB::unprepared('
            CREATE TRIGGER tr_audit_appointments_delete
            AFTER DELETE ON appointments
            FOR EACH ROW
            BEGIN
                INSERT INTO audit_logs (user_id, action, table_name, details, created_at, updated_at)
                VALUES (
                    OLD.user_id, 
                    "DELETE", 
                    "appointments", 
                    CONCAT("Appointment deleted. Date was: ", OLD.appointment_date), 
                    NOW(), 
                    NOW()
                );
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: drop all triggers
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_update');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_delete');
    }
};