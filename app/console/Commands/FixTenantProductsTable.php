<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixTenantProductsTable extends Command
{
    protected $signature = 'tenants:fix-products-table';
    protected $description = 'Runs the fix products table migration for all tenant databases';

    public function handle()
    {
        $tenants = Tenant::all();
        $this->info("Found {$tenants->count()} tenants. Starting products table schema fix...");
        
        $succeeded = 0;
        $failed = 0;
        
        foreach ($tenants as $tenant) {
            try {
                // Initialize the tenant to set up the connection
                tenancy()->initialize($tenant);
                
                // Check if we need to run the migration
                $needsMigration = false;
                
                if (Schema::hasTable('products')) {
                    // Check if all required columns exist
                    if (!Schema::hasColumn('products', 'image')) {
                        $needsMigration = true;
                        $this->info("Products table in tenant {$tenant->name} is missing the image column");
                    } else {
                        $this->info("Products table in tenant {$tenant->name} already has the correct schema");
                    }
                } else {
                    $needsMigration = true;
                    $this->info("Products table doesn't exist for tenant {$tenant->name}");
                }
                
                if ($needsMigration) {
                    // Run the migration
                    $this->info("Running migration for {$tenant->name}...");
                    
                    // Clear any previous schema cache
                    DB::purge();
                    
                    // Run the migration
                    $output = Artisan::call('migrate', [
                        '--path' => 'database/migrations/2025_05_07_140311_fix_products_table_schema.php',
                        '--force' => true
                    ]);
                    
                    if ($output === 0) {
                        $this->info("Migration completed for {$tenant->name}");
                        Log::info("Fixed products table schema for tenant: {$tenant->id} ({$tenant->name})");
                        $succeeded++;
                    } else {
                        $this->error("Migration command returned non-zero status for {$tenant->name}");
                        Log::error("Migration command failed for tenant: {$tenant->id} ({$tenant->name})");
                        $failed++;
                    }
                } else {
                    $succeeded++;
                }
                
                // End tenancy
                tenancy()->end();
            } catch (\Exception $e) {
                $this->error("Error fixing products table for tenant {$tenant->name}: {$e->getMessage()}");
                Log::error("Error fixing products table for tenant {$tenant->id} ({$tenant->name})", [
                    'tenant' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failed++;
                
                // End tenancy even if there was an error
                try {
                    tenancy()->end();
                } catch (\Exception $ex) {
                    // Ignore errors when ending tenancy
                }
            }
        }
        
        $this->info("Completed fixing products table schema for all tenants. Success: {$succeeded}, Failed: {$failed}");
        return Command::SUCCESS;
    }
} 