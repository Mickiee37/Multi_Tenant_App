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

        // Generate a unique database name
        $databaseName = 'tenant_' . Str::slug($request->domain) . '_' . Str::random(8);

        // Create tenant application
        $application = TenantApplication::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'domain' => $request->domain,
            'database_name' => $databaseName,
            'status' => 'pending'
        ]);

        // Send confirmation email
        try {
            $emailContent = "
                <h2>Thank you for your application!</h2>
                <p>Dear {$request->first_name},</p>
                <p>We have received your tenant application. Our admin team will review it shortly.</p>
                <p>Your requested domain: {$request->domain}.localhost</p>
                <p>We will notify you once your application has been processed.</p>
                <br>
                <p>Best regards,<br>Your Multi-Tenant Team</p>
            ";

            $this->gmailService->sendEmail(
                $request->email,
                'Tenant Application Received',
                $emailContent
            );
        } catch (\Exception $e) {
            Log::error('Failed to send confirmation email: ' . $e->getMessage());
        }

        return redirect()->route('tenant.register.success')
            ->with('success', 'Your application has been submitted successfully. Please check your email for confirmation.');
    }

    public function approve(TenantApplication $application)
    {
        \Log::info('Approve method called', ['application_id' => $application->id]);

        // Check if application is already approved
        if ($application->status === 'approved') {
            return back()->with('error', 'Application is already approved.');
        }

        try {
            // Ensure we're using the main database for tenant application updates
            DB::setDefaultConnection('mysql');
            DB::beginTransaction();

            // Validate application has required fields
            if (!$application->domain || !$application->email) {
                throw new \Exception('Application missing required fields (domain or email)');
            }

            // Check if tenant exists and delete if it does
            if (Tenant::find($application->domain)) {
                \Log::info('Deleting existing tenant', ['domain' => $application->domain]);
                Tenant::find($application->domain)->delete();
            }

            \Log::info('Generating password and creating tenant');
            
            // Generate password for the tenant
            $generatedPassword = Str::random(10);
            \Log::info('Password generated', ['password' => $generatedPassword]);

            try {
                // Create tenant record
                $tenant = Tenant::create([
                    'id' => $application->domain,
                    'name' => $application->first_name . ' ' . $application->last_name,
                    'domain' => $application->domain,
                    'database' => $application->database_name,
                    'data' => [
                        'name' => $application->first_name . ' ' . $application->last_name,
                        'domain' => $application->domain,
                        'database' => $application->database_name,
                        'initial_password' => $generatedPassword
                    ]
                ]);

                // Create domain
                $tenant->domains()->create([
                    'domain' => $application->domain . '.localhost',
                ]);

                // Create database directly
                try {
                    // Get database configuration
                    $host = config('database.connections.mysql.host');
                    $username = config('database.connections.mysql.username');
                    $dbPassword = config('database.connections.mysql.password');

                    // Create database using direct MySQL commands
                    DB::unprepared("CREATE DATABASE IF NOT EXISTS `{$application->database_name}`");
                    
                    // Switch to the new database
                    DB::unprepared("USE `{$application->database_name}`");
                    
                    // Create users table
                    DB::unprepared("
                        CREATE TABLE IF NOT EXISTS `users` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `email` varchar(255) NOT NULL,
                            `email_verified_at` timestamp NULL DEFAULT NULL,
                            `password` varchar(255) NOT NULL,
                            `is_admin` tinyint(1) NOT NULL DEFAULT '0',
                            `remember_token` varchar(100) DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `users_email_unique` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");

                    // Create the admin user directly
                    $hashedPassword = Hash::make($generatedPassword);
                    $now = now()->format('Y-m-d H:i:s');
                    
                    \Log::info('Creating user with password', [
                        'email' => $application->email,
                        'password' => $generatedPassword,
                        'hashed_password' => $hashedPassword
                    ]);

                    DB::unprepared("
                        INSERT INTO `{$application->database_name}`.`users` 
                        (`name`, `email`, `password`, `is_admin`, `created_at`, `updated_at`)
                        VALUES (
                            '{$application->first_name} {$application->last_name}',
                            '{$application->email}',
                            '{$hashedPassword}',
                            1,
                            '{$now}',
                            '{$now}'
                        )
                    ");

                    // Send approval email with credentials
                    $domainUrl = 'http://' . $application->domain . '.localhost:8000';
                    $emailContent = "
                        <h2>Congratulations! Your Tenant Application is Approved</h2>
                        <p>Dear {$application->first_name},</p>
                        <p>Your tenant application has been approved. Here are your login credentials:</p>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li><strong>Domain:</strong> {$domainUrl}</li>
                            <li><strong>Email:</strong> {$application->email}</li>
                            <li><strong>Password:</strong> {$generatedPassword}</li>
                        </ul>
                        <p style='color: red; font-weight: bold;'>Please save these credentials and change your password after your first login.</p>
                        <p><a href='{$domainUrl}' style='display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Click here to access your domain</a></p>
                        <br>
                        <p>Best regards,<br>Your Multi-Tenant Team</p>
                    ";

                    \Log::info('Preparing to send email with credentials', [
                        'email' => $application->email,
                        'domain' => $domainUrl,
                        'password' => $generatedPassword
                    ]);

                    try {
                        $this->gmailService->sendEmail(
                            $application->email,
                            'Tenant Application Approved - Your Login Credentials',
                            $emailContent
                        );
                        \Log::info('Approval email sent successfully');
                    } catch (\Exception $e) {
                        \Log::error('Failed to send approval email', [
                            'error' => $e->getMessage(),
                            'email_content' => $emailContent
                        ]);
                        throw $e;
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to create database or user', ['error' => $e->getMessage()]);
                    throw new \Exception('Failed to create database or user: ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create tenant', [
                    'error' => $e->getMessage(),
                    'application' => $application->toArray()
                ]);
                throw new \Exception('Failed to create tenant: ' . $e->getMessage());
            }

            // Update application status
            DB::setDefaultConnection('mysql'); // Ensure we're using the main database
            $mainDb = config('database.connections.mysql.database');
            DB::statement("USE `{$mainDb}`");
            DB::table('tenant_applications')
                ->where('id', $application->id)
                ->update([
                    'status' => 'approved',
                    'updated_at' => now()
                ]);

            DB::commit();
            \Log::info('Application approved successfully');
            return back()->with('success', "Application approved! Credentials have been sent to {$application->email}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in approval process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', $e->getMessage())->withInput();
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
        // Get the current tenant's domain from the request
        $host = request()->getHost();
        $domain = str_replace('.localhost:8000', '', $host);
        $domain = str_replace('.localhost', '', $domain);

        // Get applications only for the current tenant
        $applications = TenantApplication::where('domain', $domain)->get();

        return view('admin.tenant-applications', [
            'applications' => $applications
        ]);
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