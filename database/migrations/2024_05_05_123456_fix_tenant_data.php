<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function createTenantTables($database)
    {
        // Switch to tenant database
        config(['database.connections.tenant.database' => $database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Create users table if it doesn't exist
        if (!Schema::connection('tenant')->hasTable('users')) {
            Schema::connection('tenant')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->boolean('is_admin')->default(false);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Create products table if it doesn't exist
        if (!Schema::connection('tenant')->hasTable('products')) {
            Schema::connection('tenant')->create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('price', 10, 2);
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }

        // Create password_reset_tokens table if it doesn't exist
        if (!Schema::connection('tenant')->hasTable('password_reset_tokens')) {
            Schema::connection('tenant')->create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Create failed_jobs table if it doesn't exist
        if (!Schema::connection('tenant')->hasTable('failed_jobs')) {
            Schema::connection('tenant')->create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // Create personal_access_tokens table if it doesn't exist
        if (!Schema::connection('tenant')->hasTable('personal_access_tokens')) {
            Schema::connection('tenant')->create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function up()
    {
        // Get all tenant applications
        $applications = DB::table('tenant_applications')->get();

        foreach ($applications as $application) {
            // Find corresponding tenant
            $tenant = DB::table('tenants')
                ->where('data->email', $application->email)
                ->first();

            if ($tenant) {
                // Update tenant's database name and domain to match application
                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update([
                        'database' => $application->database_name,
                        'domain' => $application->domain . '.localhost:8000'
                    ]);

                // Update domains table
                DB::table('domains')
                    ->where('tenant_id', $tenant->id)
                    ->update([
                        'domain' => $application->domain . '.localhost:8000'
                    ]);

                // Handle database creation and migration
                try {
                    // Create new database if it doesn't exist
                    DB::statement("CREATE DATABASE IF NOT EXISTS {$application->database_name}");

                    // Create all required tables in the tenant database
                    $this->createTenantTables($application->database_name);

                    // If old database exists and is different, copy data
                    if ($tenant->database !== $application->database_name && 
                        DB::select("SHOW DATABASES LIKE '{$tenant->database}'")) {
                        
                        // Copy data from old database
                        $tables = ['users', 'products', 'password_reset_tokens', 'failed_jobs', 'personal_access_tokens'];
                        foreach ($tables as $table) {
                            if (Schema::connection('tenant')->hasTable($table)) {
                                DB::statement("
                                    INSERT INTO {$application->database_name}.{$table}
                                    SELECT * FROM {$tenant->database}.{$table}
                                ");
                            }
                        }

                        // Drop old database
                        DB::statement("DROP DATABASE IF EXISTS {$tenant->database}");
                    }

                    // Ensure admin user exists
                    $adminData = json_decode($tenant->data, true);
                    DB::connection('tenant')->table('users')
                        ->updateOrInsert(
                            ['email' => $adminData['email']],
                            [
                                'name' => $adminData['name'],
                                'password' => $adminData['password'],
                                'is_admin' => true,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );

                } catch (\Exception $e) {
                    \Log::error("Failed to setup database for tenant {$tenant->id}: " . $e->getMessage());
                }
            }
        }

        // Switch back to default connection
        DB::purge('tenant');
        DB::reconnect('mysql');
    }

    public function down()
    {
        // This migration cannot be reversed as it fixes data inconsistencies
    }
}; 