<?php

namespace App\Http\Controllers;

use App\Models\TenantApplication;
use App\Models\Tenant;
use App\Services\TenantDatabaseService;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class TenantApplicationController extends Controller
{
    protected $databaseService;
    protected $gmailService;

    public function __construct(TenantDatabaseService $databaseService, GmailService $gmailService)
    {
        $this->databaseService = $databaseService;
        $this->gmailService = $gmailService;
    }

    public function showRegistrationForm()
    {
        return view('tenant.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'domain' => 'required|string|max:255|unique:tenants,domain|regex:/^[a-z0-9-]+$/',
        ]);

        // Generate a unique tenant ID that will be used for both tenant ID and database name
        $tenantId = 'tenant_' . Str::slug($request->domain) . '_' . Str::random(8);
        $databaseName = $tenantId;  // Use the same ID for database name

        // Create tenant application
        $application = TenantApplication::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'domain' => strtolower($request->domain),
            'database_name' => $databaseName,
            'status' => 'pending'
        ]);

        // Send confirmation email
        try {
            $emailContent = view('emails.tenant-application-received')
                ->with([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'company' => $request->domain,
                ])
                ->render();

            $this->gmailService->sendEmail(
                $request->email,
                'Tenant Application Received',
                $emailContent
            );

            \Log::info('Confirmation email sent successfully', [
                'email' => $request->email,
                'domain' => $request->domain
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send confirmation email: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return redirect()->route('tenant.register.success')
            ->with('success', 'Your application has been submitted successfully. Please check your email for confirmation.');
    }

    public function approve(TenantApplication $application)
    {
        \Log::info('Starting tenant approval process', ['application_id' => $application->id]);

        if ($application->status === 'approved') {
            return back()->with('error', 'Application is already approved.');
        }

        try {
            DB::beginTransaction();

            // 1. Create the tenant first (ensure database name is lowercase)
            $dbName = strtolower($application->database_name);
            $generatedPassword = Str::random(12);
            $now = now()->format('Y-m-d H:i:s');
            
            // Include port in domain
            $domain = strtolower($application->domain . '.localhost:8000');
            
            // Use the existing database name as tenant ID
            $tenantId = $dbName;
            
            $tenantData = [
                'name' => $application->first_name . ' ' . $application->last_name,
                'email' => $application->email,
                'password' => Hash::make($generatedPassword),
                'domain' => $domain
            ];

            // Create tenant instance first
            $tenant = new \App\Models\Tenant();
            $tenant->id = $tenantId;
            $tenant->name = $tenantData['name'];
            $tenant->domain = $domain;
            $tenant->database = $dbName;
            $tenant->data = $tenantData;
            $tenant->save();

            // 2. Create domain for the tenant
            $domain = $tenant->domains()->create([
                'domain' => $domain
            ]);

            // 3. Initialize the tenant (this will create the database)
            $tenant->createDatabase();
            
            // 4. Run migrations for the tenant
            $tenant->run(function () use ($tenant, $tenantData, $now) {
                // Create users table
                if (!Schema::hasTable('users')) {
                    Schema::create('users', function ($table) {
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

                // Create admin user
                DB::table('users')->insert([
                    'name' => $tenantData['name'],
                    'email' => $tenantData['email'],
                    'password' => $tenantData['password'],
                    'is_admin' => true,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            });

            // 5. Update application status
            $application->update([
                'status' => 'approved',
                'updated_at' => $now
            ]);

            // 6. Send approval email with port number for the URL
            $domainUrl = 'http://' . $tenantData['domain'] . ':8000';
            $emailContent = view('emails.tenant-approved', [
                'name' => $application->first_name,
                'domain' => $domainUrl,
                'email' => $application->email,
                'password' => $generatedPassword
            ])->render();

                    try {
                        $this->gmailService->sendEmail(
                            $application->email,
                            'Tenant Application Approved - Your Login Credentials',
                            $emailContent
                        );
                \Log::info('Approval email sent successfully', ['email' => $application->email]);
            } catch (\Exception $e) {
                \Log::error('Failed to send approval email', [
                    'error' => $e->getMessage(),
                    'email' => $application->email
                ]);
                // Continue even if email fails
            }

            DB::commit();
            \Log::info('Tenant approval process completed successfully', [
                'tenant_id' => $tenant->id,
                'database' => $dbName
            ]);

            return back()->with('success', "Application approved! Credentials have been sent to {$application->email}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Tenant approval process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to approve tenant application: ' . $e->getMessage());
        }
    }

    public function reject(TenantApplication $application)
    {
        try {
            $application->update(['status' => 'rejected']);

            // Send rejection email
            $emailContent = "
                <h2>Tenant Application Status Update</h2>
                <p>Dear {$application->first_name},</p>
                <p>We regret to inform you that your tenant application has been declined.</p>
                <p>If you have any questions, please feel free to contact us.</p>
                <br>
                <p>Best regards,<br>Your Multi-Tenant Team</p>
            ";

            $this->gmailService->sendEmail(
                $application->email,
                'Tenant Application Status Update',
                $emailContent
            );

            return back()->with('success', 'Application rejected successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send rejection email: ' . $e->getMessage());
            return back()->with('warning', 'Application rejected but failed to send notification email.');
        }
    }

    public function adminDashboard()
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
            } else {
                // Check if image column exists, if not add it
                if (!Schema::connection('tenant')->hasColumn('products', 'image')) {
                    Schema::connection('tenant')->table('products', function ($table) {
                        $table->string('image')->nullable();
                    });
                }
            }
            
            // Get products from tenant database
            $products = DB::connection('tenant')->table('products')->get();
            
            \Log::info('Loading admin dashboard', [
                'tenant_id' => $tenant->id,
                'database' => $tenant->database,
                'products_count' => $products->count()
            ]);

            return view('tenant.dashboard', compact('products'));
        } catch (\Exception $e) {
            \Log::error('Error in admin dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('login')->with('error', 'Error loading dashboard: ' . $e->getMessage());
        }
    }

    private function getTenantFromDomain()
    {
        // Get the current host and ensure it's lowercase
        $host = strtolower(request()->getHost());
        
        // Find tenant by full domain including .localhost:8000
        $tenant = DB::connection('mysql')->table('tenants')
            ->where('domain', $host)
            ->first();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        return $tenant;
    }

    public function storeProduct(Request $request)
    {
        try {
            $tenant = tenant();
            
            if (!$tenant) {
                \Log::error('No tenant found for request');
                return redirect()->route('login')->with('error', 'Tenant not found');
            }

            // Configure tenant connection
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'description' => 'nullable|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                
                \Log::info('Storing product in tenant database', [
                    'tenant_id' => $tenant->id,
                    'database' => $tenant->database,
                    'image_path' => $imagePath
                ]);

                // Insert into tenant database
                DB::connection('tenant')->table('products')->insert([
                    'name' => $validated['name'],
                    'price' => $validated['price'],
                    'description' => $validated['description'],
                    'image' => $imagePath,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return redirect()->route('tenant.admin.dashboard')
                    ->with('success', 'Product added successfully');
            }

            return redirect()->back()->with('error', 'Image upload failed');
        } catch (\Exception $e) {
            \Log::error('Error storing product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Error adding product: ' . $e->getMessage());
        }
    }

    public function editProduct($id)
    {
        $tenant = $this->getTenantFromDomain();
        
        if (!$tenant) {
            return redirect()->route('home')->with('error', 'Invalid tenant');
        }

        // Configure tenant connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Get product from tenant database
        $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function updateProduct(Request $request, $id)
    {
        $tenant = $this->getTenantFromDomain();
        
        if (!$tenant) {
            return redirect()->route('home')->with('error', 'Invalid tenant');
        }

        // Configure tenant connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Get product from tenant database
        $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
        
        if (!$product) {
            return redirect()->back()->with('error', 'Product not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $updateData = [
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'],
            'updated_at' => now()
        ];

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            
            // Store new image
            $updateData['image'] = $request->file('image')->store('products', 'public');
        }

        // Update product in tenant database
        DB::connection('tenant')->table('products')
            ->where('id', $id)
            ->update($updateData);

        return redirect()->route('tenant.admin.dashboard')
            ->with('success', 'Product updated successfully');
    }

    public function deleteProduct($id)
    {
        $tenant = $this->getTenantFromDomain();
        
        if (!$tenant) {
            return redirect()->route('home')->with('error', 'Invalid tenant');
        }

        // Configure tenant connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Get product from tenant database
        $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Delete the image file if it exists
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        
        // Delete the product from tenant database
        DB::connection('tenant')->table('products')->where('id', $id)->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function requestBackup(Request $request, $tenantId)
    {
        $tenant = TenantApplication::findOrFail($tenantId);
        $backupFile = $this->databaseService->createBackup($tenant->database_name);
        
        return response()->download(
            storage_path("app/backups/{$backupFile}"),
            $backupFile
        );
    }
} 