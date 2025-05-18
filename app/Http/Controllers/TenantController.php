<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class TenantController extends Controller
{
    public function __construct()
    {
        // Middleware is handled at the route level
    }

    public function dashboard()
    {
        try {
            // Get the current tenant
            $tenant = tenant();
            
            if (!$tenant) {
                \Log::error('No tenant found for request');
                return redirect()->route('login')->with('error', 'Tenant not found');
            }

            // Configure tenant connection
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Direct schema fix (bypass migration if it's not working)
            $needsRepairing = false;
            
            if (Schema::connection('tenant')->hasTable('products')) {
                // Check if the image column exists
                if (!Schema::connection('tenant')->hasColumn('products', 'image')) {
                    $needsRepairing = true;
                    \Log::info('Products table is missing the image column, performing manual schema fix...');
                }
            } else {
                $needsRepairing = true;
                \Log::info('Products table does not exist, creating it manually...');
            }
            
            if ($needsRepairing) {
                try {
                    // Backup existing products data if table exists
                    $existingProducts = [];
                    if (Schema::connection('tenant')->hasTable('products')) {
                        $existingProducts = DB::connection('tenant')->table('products')->get()->toArray();
                        \Log::info("Backed up " . count($existingProducts) . " existing products before recreation");
                        
                        // Drop the existing table
                        Schema::connection('tenant')->dropIfExists('products');
                    }
                    
                    // Create the products table with all required columns
                    Schema::connection('tenant')->create('products', function ($table) {
                        $table->id();
                        $table->string('name');
                        $table->decimal('price', 10, 2);
                        $table->text('description')->nullable();
                        $table->string('image')->nullable();
                        $table->timestamps();
                    });
                    
                    \Log::info('Products table created successfully with the correct schema');
                    
                    // Restore existing products data if any
                    if (!empty($existingProducts)) {
                        foreach ($existingProducts as $product) {
                            // Convert object to array and ensure all required fields exist
                            $productData = (array) $product;
                            
                            // Make sure image field exists even if it was missing before
                            if (!isset($productData['image'])) {
                                $productData['image'] = null;
                            }
                            
                            // Insert the product back into the table
                            DB::connection('tenant')->table('products')->insert($productData);
                        }
                        \Log::info("Restored " . count($existingProducts) . " products after table recreation");
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to repair products table schema manually', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Get products from tenant database - use try/catch in case there are still issues
            try {
                // Get products with strict database access
                $products = DB::connection('tenant')
                    ->table('products')
                    ->select('id', 'name', 'price', 'description', 'image', 'created_at', 'updated_at')
                    ->get();
                
                \Log::info('Fetched products from tenant database', [
                    'tenant' => $tenant->id, 
                    'count' => $products->count(),
                    'products' => $products->toArray()
                ]);
            } catch (\Exception $e) {
                \Log::error('Error fetching products', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $products = collect(); // Empty collection if there's an error
            }
            
            // Check if we got any products
            if ($products->isEmpty()) {
                \Log::warning('No products found for tenant', ['tenant' => $tenant->id]);
            }
            
            return view('tenant.dashboard', [
                'products' => $products,
                'tenant' => $tenant
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in tenant dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('login')->with('error', 'Error loading dashboard: ' . $e->getMessage());
        }
    }
} 