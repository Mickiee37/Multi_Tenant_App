<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupTenantDatabases extends Command
{
    protected $signature = 'tenants:setup-databases';
    protected $description = 'Set up products table in each tenant database';

    public function handle()
    {
        $this->info('Starting to set up tenant databases...');

        // Get all tenants
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->domain}");

            try {
                // Configure and switch to tenant database
                config(['database.connections.tenant.database' => $tenant->database]);
                DB::purge('tenant');
                DB::reconnect('tenant');
                
                // Create products table in tenant database
                Schema::connection('tenant')->create('products', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->decimal('price', 10, 2);
                    $table->text('description')->nullable();
                    $table->string('image')->nullable();
                    $table->timestamps();
                });

                // Get products from central database for this tenant
                $products = DB::connection('mysql')
                    ->table('products')
                    ->get();

                // Move products to tenant database
                foreach ($products as $product) {
                    DB::connection('tenant')->table('products')->insert([
                        'name' => $product->name,
                        'price' => $product->price,
                        'description' => $product->description,
                        'image' => $product->image,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at
                    ]);
                }

                $this->info("Successfully set up database for tenant: {$tenant->domain}");

            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->domain}: " . $e->getMessage());
            }
        }

        // Switch back to central database
        DB::setDefaultConnection('mysql');

        // Drop products table from central database
        if (Schema::hasTable('products')) {
            Schema::dropIfExists('products');
            $this->info('Removed products table from central database');
        }

        $this->info('Database setup completed!');
    }
} 