<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DatabaseBackup extends Command
{
    // Command signature
    protected $signature = 'db:backup';

    // Command description
    protected $description = 'Create a backup of the database';

    public function handle()
    {
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $database = env('DB_DATABASE');
        $host     = env('DB_HOST');
        $port = env('DB_PORT', '3307');

        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup-{$date}.sql";
        $path = storage_path('app/backups');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = "{$path}/{$filename}";

        // Fix applied here

        // 1. Set mysqldump absolute path
        $mysqldumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

        // 2. Quote path if it has spaces
        $command = "\"{$mysqldumpPath}\" --user=\"{$username}\" --host={$host} --port={$port} {$database} > \"{$filePath}\"";
        // Use DB password from .env if set
        if (!empty($password)) {
            $command = "\"{$mysqldumpPath}\" --user={$username} --password={$password} --host={$host} {$database} > \"{$filePath}\"";
        }

        // ---

        $output = null;
        $resultCode = null;

        // Run command
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->info("Backup Successful: " . $filename);
            Log::info("Database backup created: " . $filename);
        } else {
            // Show command output on error
            $this->error("Backup Failed. Output: " . implode("\n", $output));
            Log::error("Database backup failed.");
        }
    }
}
