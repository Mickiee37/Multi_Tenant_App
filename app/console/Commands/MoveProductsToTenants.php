<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MoveProductsToTenants extends Command
{
    protected $signature = 'tenants:move-products';
    protected $description = 'Move products from central database to tenant databases';

    public function handle()
    {
        $this->info('Starting to move products to tenant databases...');

        // Get all tenants
        $tenants = DB::table('tenants')->get();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->domain}");

            // Configure and switch to tenant database
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Create products table in tenant database if it doesn't exist
            if (!Schema::connection('tenant')->hasTable('products')) {
                $this->call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/tenant'
                ]);
            }

            // Get all products from central database
            $products = DB::connection('mysql')->table('products')->get();

            // Insert products into tenant database
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

            $this->info("Moved " . count($products) . " products to tenant: {$tenant->domain}");
        }

        // Switch back to central database
        DB::setDefaultConnection('mysql');

        // Drop products table from central database
        Schema::dropIfExists('products');

        $this->info('Successfully moved all products to tenant databases!');
    }
} 