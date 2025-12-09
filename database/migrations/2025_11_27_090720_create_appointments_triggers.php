<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing triggers if they exist (for MySQL)
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_update');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_delete');
        }

        // Only create triggers for MySQL (SQLite has limited trigger support)
        if (DB::getDriverName() === 'mysql') {
            // TRIGGER 1: Log when a NEW appointment is created
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

            // TRIGGER 2: Log when appointment STATUS is updated
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
        // SQLite: Triggers are skipped as SQLite has limited trigger support
        // Use Laravel Events/Observers instead for audit logging in SQLite environments
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_update');
            DB::unprepared('DROP TRIGGER IF EXISTS tr_audit_appointments_delete');
        }
    }
};
