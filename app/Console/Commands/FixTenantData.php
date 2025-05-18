<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixTenantData extends Command
{
    protected $signature = 'tenants:fix-data';
    protected $description = 'Fix tenant data inconsistencies between tables and databases';

    public function handle()
    {
        $this->info('Starting tenant data fix...');
        
        // Backup current state
        $this->info('Creating backup of current state...');
        try {
            // Get list of all tenant databases
            $tenants = DB::table('tenants')->get();
            
            // Create backups directory if it doesn't exist
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }
            
            foreach ($tenants as $tenant) {
                $backupFile = storage_path("app/backups/{$tenant->database}_" . date('Y_m_d_His') . ".sql");
                exec("mysqldump -u " . env('DB_USERNAME') . " -p" . env('DB_PASSWORD') . " {$tenant->database} > {$backupFile} 2>/dev/null");
                $this->info("Backed up database: {$tenant->database}");
            }
        } catch (\Exception $e) {
            $this->error('Failed to create backup: ' . $e->getMessage());
            if (!$this->confirm('Continue without complete backup?')) {
                return 1;
            }
        }

        // Run the migration
        $this->info('Running migration...');
        try {
            // First check if migration exists
            $migrationPath = database_path('migrations/2024_05_05_123456_fix_tenant_data.php');
            if (!file_exists($migrationPath)) {
                $this->error('Migration file not found: ' . $migrationPath);
                return 1;
            }

            $this->call('migrate', [
                '--path' => 'database/migrations/2024_05_05_123456_fix_tenant_data.php',
                '--force' => true
            ]);
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }

        // Verify the changes
        $this->info('Verifying changes...');
        $applications = DB::table('tenant_applications')->get();
        $inconsistencies = [];

        foreach ($applications as $application) {
            $tenant = DB::table('tenants')
                ->where('data->email', $application->email)
                ->first();

            if ($tenant) {
                if ($tenant->database !== $application->database_name) {
                    $inconsistencies[] = "Database name mismatch for email {$application->email}";
                }
                if ($tenant->domain !== $application->domain . '.localhost:8000') {
                    $inconsistencies[] = "Domain mismatch for email {$application->email}";
                }
            }
        }

        if (count($inconsistencies) > 0) {
            $this->warn('Some inconsistencies remain:');
            foreach ($inconsistencies as $inconsistency) {
                $this->warn("- $inconsistency");
            }
        } else {
            $this->info('All data has been fixed successfully!');
        }

        return 0;
    }
} 