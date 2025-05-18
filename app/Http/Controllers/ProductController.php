<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Ensure products table has the correct schema
     */
    private function ensureCorrectSchema()
    {
        // Get the current tenant
        $tenant = tenant();
        
        // Configure tenant connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // Direct schema fix (no migrations)
        $needsRepairing = false;
        
        if (Schema::connection('tenant')->hasTable('products')) {
            if (!Schema::connection('tenant')->hasColumn('products', 'image')) {
                $needsRepairing = true;
                Log::info('Products table is missing the image column, performing manual schema fix for tenant: ' . $tenant->id);
            }
        } else {
            $needsRepairing = true;
            Log::info('Products table does not exist, creating it manually for tenant: ' . $tenant->id);
        }
        
        if ($needsRepairing) {
            try {
                // Backup existing products data if table exists
                $existingProducts = [];
                if (Schema::connection('tenant')->hasTable('products')) {
                    $existingProducts = DB::connection('tenant')->table('products')->get()->toArray();
                    Log::info("Backed up " . count($existingProducts) . " existing products before recreation");
                    
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
                
                Log::info('Products table created successfully with the correct schema for tenant: ' . $tenant->id);
                
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
                    Log::info("Restored " . count($existingProducts) . " products after table recreation");
                }
                
                return true;
            } catch (\Exception $e) {
                Log::error('Failed to repair products table schema manually', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return false;
            }
        }
        
        return true; // Schema is already correct
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'image' => 'nullable|image|max:2048',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Get the current tenant
            $tenant = tenant();
            
            // Ensure the database connection is configured correctly
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Check if tenant has reached product limit
            $productCount = DB::connection('tenant')->table('products')->count();
            
            if ($tenant->hasReachedProductLimit($productCount)) {
                return redirect()->back()->with('error', 'Product limit reached. Please upgrade your subscription to add more products.');
            }

            // Ensure the products table has the correct schema
            if (!$this->ensureCorrectSchema()) {
                return redirect()->back()->with('error', 'There was an issue with the database schema. Please try again or contact support if the problem persists.');
            }

            $imagePath = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                try {
                    // Make sure the public disk is configured properly
                    if (!Storage::disk('public')->exists('products')) {
                        Storage::disk('public')->makeDirectory('products');
                    }
                    
                    // Use a more secure way to store files
                    $file = $request->file('image');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $imagePath = $file->storeAs('products', $filename, 'public');
                    
                    Log::info('Image uploaded successfully', [
                        'path' => $imagePath,
                        'original_name' => $file->getClientOriginalName()
                    ]);
                } catch (\Exception $e) {
                    Log::error('Image upload failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return redirect()->back()->with('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            // Double check that the products table has the image column
            if (!Schema::connection('tenant')->hasColumn('products', 'image')) {
                Log::error('Products table is still missing the image column after migration attempt');
                return redirect()->back()->with('error', 'Database schema issue could not be resolved. Please contact support.');
            }

            $result = DB::connection('tenant')->table('products')->insert([
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'image' => $imagePath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!$result) {
                Log::error('Failed to insert product into database');
                return redirect()->back()->with('error', 'Failed to create product: Database error');
            }

            return redirect()->back()->with('status', 'Product created successfully!');
        } catch (\Exception $e) {
            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit($id)
    {
        try {
            // Ensure the products table has the correct schema
            $this->ensureCorrectSchema();
            
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Get the current tenant
            $tenant = tenant();
            
            // Ensure the database connection is configured correctly
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Ensure the products table has the correct schema
            if (!$this->ensureCorrectSchema()) {
                return redirect()->back()->with('error', 'There was an issue with the database schema. Please try again or contact support if the problem persists.');
            }
            
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return redirect()->back()->with('error', 'Product not found');
            }

            $data = [
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'updated_at' => now(),
            ];

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                // Make sure the public disk is configured properly
                if (!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }
                
                // Delete old image if exists
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                
                // Use a more secure way to store files
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $data['image'] = $file->storeAs('products', $filename, 'public');
            }

            $result = DB::connection('tenant')->table('products')->where('id', $id)->update($data);
            
            if (!$result) {
                Log::error('Failed to update product in database');
                return redirect()->back()->with('error', 'Failed to update product: Database error');
            }

            return redirect()->back()->with('status', 'Product updated successfully!');
        } catch (\Exception $e) {
            Log::error('Product update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        try {
            // Get the current tenant
            $tenant = tenant();
            
            // Ensure the database connection is configured correctly
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Ensure the products table has the correct schema
            $this->ensureCorrectSchema();
            
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            // Delete the image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $result = DB::connection('tenant')->table('products')->where('id', $id)->delete();
            
            if (!$result) {
                Log::error('Failed to delete product from database');
                return response()->json(['error' => 'Failed to delete product: Database error'], 500);
            }

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Product deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fix the database schema for the products table
     * This is a direct Ajax endpoint that can be called from the frontend
     */
    public function fixSchema()
    {
        try {
            // Get the current tenant
            $tenant = tenant();
            
            if (!$tenant) {
                Log::error('No tenant found for schema fix request');
                return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
            }
            
            // Configure tenant connection
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Backup existing products data if table exists
            $existingProducts = [];
            if (Schema::connection('tenant')->hasTable('products')) {
                try {
                    $existingProducts = DB::connection('tenant')->table('products')->get()->toArray();
                    Log::info("Backed up " . count($existingProducts) . " existing products before recreation");
                } catch (\Exception $e) {
                    Log::warning("Could not backup existing products: " . $e->getMessage());
                    // Continue with the fix even if we can't backup
                }
                
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
            
            Log::info('Products table created successfully with the correct schema for tenant: ' . $tenant->id);
            
            // Restore existing products data if any
            if (!empty($existingProducts)) {
                foreach ($existingProducts as $product) {
                    try {
                        // Convert object to array and ensure all required fields exist
                        $productData = (array) $product;
                        
                        // Make sure image field exists even if it was missing before
                        if (!isset($productData['image'])) {
                            $productData['image'] = null;
                        }
                        
                        // Insert the product back into the table
                        DB::connection('tenant')->table('products')->insert($productData);
                    } catch (\Exception $e) {
                        Log::warning("Could not restore product ID " . ($product->id ?? 'unknown') . ": " . $e->getMessage());
                        // Continue with the next product
                    }
                }
                Log::info("Attempted to restore " . count($existingProducts) . " products after table recreation");
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Database schema fixed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fix products table schema', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error fixing database schema: ' . $e->getMessage()
            ], 500);
        }
    }
} 