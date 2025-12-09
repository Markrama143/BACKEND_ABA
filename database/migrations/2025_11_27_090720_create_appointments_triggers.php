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
                    // Drop existing triggers if they exist
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_update');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_delete');

        // TRIGGER 1: Log when a NEW appointment is created
        // FIX: Changed NEW.appointment_date to NEW.date and NEW.appointment_time to NEW.time
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
                    CONCAT("New appointment booked for ", NEW.date, " at ", NEW.time), 
                    NOW(), 
                    NOW()
                );
            END
        ');

        // TRIGGER 2: Log when an appointment STATUS is updated
        DB::unprepared('
            CREATE TRIGGER tr_audit_appointments_update
            AFTER UPDATE ON appointments
            FOR EACH ROW
            BEGIN
                -- Only log if the status actually changed
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

        // TRIGGER 3: Log when an appointment is DELETED
        // FIX: Changed OLD.appointment_date to OLD.date
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
                    CONCAT("Appointment deleted. Date was: ", OLD.date), 
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
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_update');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_delete');
    }
};
