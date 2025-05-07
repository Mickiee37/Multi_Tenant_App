<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            
            // Create products table if it doesn't exist
            if (!Schema::connection('tenant')->hasTable('products')) {
                Schema::connection('tenant')->create('products', function ($table) {
                    $table->id();
                    $table->string('name');
                    $table->decimal('price', 10, 2);
                    $table->text('description')->nullable();
                    $table->string('image')->nullable();
                    $table->timestamps();
                });
            }
            
            // Get products from tenant database
            $products = DB::connection('tenant')->table('products')->get();
            
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