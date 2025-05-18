<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration will check if the products table exists and has all required columns
        // If not, it will recreate the table with the correct schema
        
        $connection = DB::connection();
        $schemaName = $connection->getDatabaseName();
        
        try {
            // Check if we're in a tenant database (not the central database)
            if (strpos($schemaName, 'tenant_') === 0) {
                Log::info("Running products table schema fix on tenant database: {$schemaName}");
                
                $needsRecreation = false;
                
                if (Schema::hasTable('products')) {
                    // Check if all required columns exist
                    if (!Schema::hasColumn('products', 'image')) {
                        $needsRecreation = true;
                        Log::info("Products table in {$schemaName} is missing the image column, will recreate the table");
                    }
                } else {
                    $needsRecreation = true;
                    Log::info("Products table doesn't exist in {$schemaName}, will create it");
                }
                
                if ($needsRecreation) {
                    // Backup existing products data if table exists
                    $existingProducts = [];
                    if (Schema::hasTable('products')) {
                        $existingProducts = DB::table('products')->get()->toArray();
                        Log::info("Backed up " . count($existingProducts) . " existing products before recreation");
                        
                        // Drop the existing table
                        Schema::dropIfExists('products');
                    }
                    
                    // Create the products table with all required columns
                    Schema::create('products', function (Blueprint $table) {
                        $table->id();
                        $table->string('name');
                        $table->decimal('price', 10, 2);
                        $table->text('description')->nullable();
                        $table->string('image')->nullable();
                        $table->timestamps();
                    });
                    
                    // Restore existing products data if any
                    if (count($existingProducts) > 0) {
                        foreach ($existingProducts as $product) {
                            // Convert object to array and ensure all required fields exist
                            $productData = (array) $product;
                            
                            // Make sure image field exists even if it was missing before
                            if (!isset($productData['image'])) {
                                $productData['image'] = null;
                            }
                            
                            // Insert the product back into the table
                            DB::table('products')->insert($productData);
                        }
                        Log::info("Restored " . count($existingProducts) . " products after table recreation");
                    }
                    
                    Log::info("Products table in {$schemaName} has been successfully recreated with the correct schema");
                } else {
                    Log::info("Products table in {$schemaName} already has the correct schema, no changes needed");
                }
            } else {
                Log::info("Skipping products table migration for non-tenant database: {$schemaName}");
            }
        } catch (\Exception $e) {
            Log::error("Error fixing products table schema in {$schemaName}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to ensure migration fails
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed as this is a schema fix
    }
};
