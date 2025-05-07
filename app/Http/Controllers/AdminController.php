<?php

namespace App\Http\Controllers;

use App\Models\TenantApplication;
use App\Services\TenantDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Services\GmailService;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    protected $tenantDatabaseService;
    protected $gmailService;

    public function __construct(TenantDatabaseService $tenantDatabaseService, GmailService $gmailService)
    {
        $this->tenantDatabaseService = $tenantDatabaseService;
        $this->gmailService = $gmailService;
    }

    public function dashboard()
    {
        $pendingApplications = TenantApplication::where('status', 'pending')->count();
        $approvedApplications = TenantApplication::where('status', 'approved')->count();
        $rejectedApplications = TenantApplication::where('status', 'rejected')->count();

        return view('admin.dashboard', compact('pendingApplications', 'approvedApplications', 'rejectedApplications'));
    }

    public function tenantApplications()
    {
        try {
            \Log::info('Starting to fetch tenant applications');
            
            $applications = TenantApplication::query()
                ->orderBy('created_at', 'desc')
                ->get();
            
            \Log::info('Tenant applications query executed', [
                'count' => $applications->count(),
                'sql' => TenantApplication::query()->orderBy('created_at', 'desc')->toSql(),
                'first_application' => $applications->first(),
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database')
            ]);

            return view('admin.tenant-applications', compact('applications'));
        } catch (\Exception $e) {
            \Log::error('Error fetching tenant applications: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('admin.tenant-applications', [
                'applications' => collect([]),
                'error' => 'Error fetching applications: ' . $e->getMessage()
            ]);
        }
    }

    public function approveTenantApplication($id)
    {
        try {
            // Enable query logging
            DB::enableQueryLog();
            
            $application = TenantApplication::findOrFail($id);
            
            if ($application->status !== 'pending') {
                return back()->with('error', 'This application has already been processed.');
            }

            // Generate a secure password
            $generatedPassword = Str::random(12);
            $hashedPassword = Hash::make($generatedPassword);

            Log::info('Starting tenant creation process', [
                'application_id' => $id,
                'domain' => $application->domain,
            ]);

            try {
                // Ensure we're on the main database connection
                DB::setDefaultConnection('mysql');
                
                // Use the database name from the application
                $tenantDomain = $application->domain;
                $databaseName = $application->database_name;

                // Step 1: Create the tenant database and its tables first (outside transaction)
                $this->tenantDatabaseService->createDatabase($application->domain, $databaseName);

                // Step 2: Begin transaction for tenant and domain creation
                DB::beginTransaction();

                try {
                    // Insert tenant record
                    DB::table('tenants')->insert([
                        'id' => $databaseName, // Use database_name as tenant ID for consistency
                        'name' => $application->first_name . ' ' . $application->last_name,
                        'domain' => $tenantDomain . '.localhost',
                        'database' => $databaseName,
                        'database_name' => $databaseName,
                        'data' => json_encode([
                            'name' => $application->first_name . ' ' . $application->last_name,
                            'email' => $application->email,
                            'database' => $databaseName
                        ]),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Insert domain record with proper formatting
                    DB::table('domains')->insert([
                        'domain' => $tenantDomain . '.localhost',
                        'tenant_id' => $databaseName, // Use database_name as tenant ID
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Configure tenant database connection
                    config(['database.connections.tenant.database' => $databaseName]);
                    DB::purge('tenant');
                    DB::reconnect('tenant');

                    // Verify the users table exists before inserting
                    if (!Schema::connection('tenant')->hasTable('users')) {
                        throw new \Exception('Users table does not exist in tenant database');
                    }

                    // Create user in tenant database
                    DB::connection('tenant')->table('users')->insert([
                        'name' => $application->first_name . ' ' . $application->last_name,
                        'email' => $application->email,
                        'password' => $hashedPassword,
                        'is_admin' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Update application status
                    $application->status = 'approved';
                    $application->save();

                    // Send approval email
                    $domainUrl = 'http://' . $application->domain . '.localhost:8000';
                    $emailContent = view('emails.tenant-application-approved')
                        ->with([
                            'name' => $application->first_name . ' ' . $application->last_name,
                            'company' => $application->domain,
                            'domain' => $domainUrl,
                            'email' => $application->email,
                            'password' => $generatedPassword
                        ])
                        ->render();

                    $this->gmailService->sendEmail(
                        $application->email,
                        'Tenant Application Approved - Your Login Credentials',
                        $emailContent
                    );

                    DB::commit();

                    return back()->with('success', 'Tenant application approved successfully. Credentials have been sent to ' . $application->email);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Failed to create tenant or domain records: ' . $e->getMessage(), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'database' => $databaseName
                    ]);
                    throw new \Exception('Failed to create tenant or domain records: ' . $e->getMessage());
                }

            } catch (\Exception $e) {
                Log::error('Error in tenant creation process: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to approve tenant application: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to approve tenant application: ' . $e->getMessage());
        }
    }

    public function rejectTenantApplication(Request $request, $id)
    {
        try {
            $application = TenantApplication::findOrFail($id);
            
            if ($application->status !== 'pending') {
                return back()->with('error', 'This application has already been processed.');
            }

            // Update application status and reason
            $application->status = 'rejected';
            $application->rejection_reason = $request->input('rejection_reason');
            $application->save();

            try {
                // Send rejection email
                $emailContent = "
                    <h2>Update on Your Tenant Application</h2>
                    <p>Dear {$application->first_name},</p>
                    <p>We regret to inform you that your tenant application has been rejected.</p>";
                
                if ($application->rejection_reason) {
                    $emailContent .= "<p><strong>Reason:</strong> {$application->rejection_reason}</p>";
                }
                
                $emailContent .= "
                    <p>If you have any questions, please feel free to contact us.</p>
                    <br>
                    <p>Best regards,<br>Your Multi-Tenant Team</p>
                ";

                $this->gmailService->sendEmail(
                    $application->email,
                    'Update on Your Tenant Application',
                    $emailContent
                );

            } catch (\Exception $e) {
                Log::error('Failed to send rejection email: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw the exception, just log it
            }

            return back()->with('success', 'Tenant application rejected successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to reject tenant application: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to reject tenant application. Please try again.');
        }
    }

    public function deactivateTenant($id)
    {
        try {
            DB::beginTransaction();

            // Find the tenant
            $tenant = Tenant::findOrFail($id);
            if (!$tenant) {
                return back()->with('error', 'Tenant not found.');
            }

            // Update application status to deactivated
            TenantApplication::where('database_name', $tenant->database)
                ->update([
                    'status' => 'deactivated',
                    'updated_at' => now()
                ]);

            // You might want to prevent access to the tenant's database here
            // For now, we'll just mark it as deactivated

            DB::commit();
            return back()->with('success', 'Tenant has been deactivated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to deactivate tenant: ' . $e->getMessage());
            return back()->with('error', 'Failed to deactivate tenant. Please try again.');
        }
    }

    /**
     * Show the manage plan page for a tenant.
     *
     * @param string $id The tenant ID
     * @return \Illuminate\View\View
     */
    public function showManagePlan($id)
    {
        try {
            $tenant = Tenant::findOrFail($id);
            
            $plans = [
                'basic' => [
                    'name' => 'Basic',
                    'productLimit' => 2,
                    'monthlyPrice' => 9.99,
                ],
                'pro' => [
                    'name' => 'Pro',
                    'productLimit' => 5,
                    'monthlyPrice' => 19.99,
                ],
                'premium' => [
                    'name' => 'Premium',
                    'productLimit' => 10,
                    'monthlyPrice' => 39.99,
                ],
                'enterprise' => [
                    'name' => 'Enterprise',
                    'productLimit' => 20,
                    'monthlyPrice' => 79.99,
                ]
            ];
            
            return view('admin.manage-plan', compact('tenant', 'plans'));
        } catch (\Exception $e) {
            Log::error('Error showing manage plan page: ' . $e->getMessage(), [
                'tenant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error retrieving tenant information: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a tenant's subscription plan.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id The tenant ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePlan(Request $request, $id)
    {
        try {
            $request->validate([
                'plan' => 'required|in:basic,pro,premium,enterprise',
                'expires_at' => 'nullable|date',
            ]);
            
            $tenant = Tenant::findOrFail($id);
            
            // Update the tenant's subscription plan
            $oldPlan = $tenant->subscription_plan ?? 'basic';
            $newPlan = $request->input('plan');
            
            $tenant->subscription_plan = $newPlan;
            
            // Update expiration date if provided
            if ($request->filled('expires_at')) {
                $tenant->subscription_expires_at = $request->input('expires_at');
            } else {
                // Default to 1 year from now
                $tenant->subscription_expires_at = now()->addYear();
            }
            
            $tenant->save();
            
            Log::info('Tenant plan updated by admin', [
                'tenant_id' => $id,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan,
                'expires_at' => $tenant->subscription_expires_at
            ]);
            
            return redirect()->route('admin.tenant-applications')
                ->with('success', "Tenant plan updated to {$newPlan} successfully.");
        } catch (\Exception $e) {
            Log::error('Error updating tenant plan: ' . $e->getMessage(), [
                'tenant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error updating tenant plan: ' . $e->getMessage());
        }
    }
} 