<?php

namespace App\Http\Controllers;

use App\Models\TenantApplication;
use App\Models\Tenant;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TenantApplicationController extends Controller
{
    protected $gmailService;

    public function __construct(GmailService $gmailService)
    {
        $this->gmailService = $gmailService;
    }

    public function showRegistrationForm()
    {
        return view('tenant.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenant_applications,email',
        ]);

        // Create tenant application
        $application = TenantApplication::create($validated);

        // Send confirmation email
        $emailContent = "
            <h2>Thank you for your application!</h2>
            <p>Dear {$validated['first_name']},</p>
            <p>We have received your tenant application. Our admin team will review it shortly.</p>
            <p>We will notify you once your application has been processed.</p>
            <br>
            <p>Best regards,<br>Your Multi-Tenant Team</p>
        ";

        try {
            $this->gmailService->sendEmail(
                $validated['email'],
                'Tenant Application Received',
                $emailContent
            );
        } catch (\Exception $e) {
            // Log the error but don't stop the process
            \Log::error('Failed to send confirmation email: ' . $e->getMessage());
        }

        return redirect()->route('tenant.register.success')
            ->with('success', 'Your application has been submitted successfully. Please wait for admin approval.');
    }

    public function adminDashboard()
    {
        $applications = TenantApplication::latest()->get();
        return view('admin.tenant-applications', compact('applications'));
    }

    public function approve(TenantApplication $application)
    {
        try {
            DB::beginTransaction();

            // Generate base domain
            $baseDomain = Str::slug($application->first_name . '-' . $application->last_name);
            
            // Check if domain exists and append number if it does
            $domain = $baseDomain;
            $counter = 1;
            while (Tenant::find($domain)) {
                $domain = $baseDomain . '-' . $counter;
                $counter++;
            }

            $username = Str::lower($application->first_name . '.' . $application->last_name);
            $password = Str::random(10);

            Log::info('Creating tenant', ['domain' => $domain]);

            // Create the tenant with unique domain
            $tenant = Tenant::create(['id' => $domain]);

            Log::info('Creating domain for tenant');

            // Create domain
            $tenant->domains()->create([
                'domain' => $domain . '.localhost',
            ]);

            Log::info('Creating tenant admin user');

            // Create the tenant's admin user
            $tenant->run(function () use ($application, $password) {
                \App\Models\User::create([
                    'name' => $application->first_name . ' ' . $application->last_name,
                    'email' => $application->email,
                    'password' => Hash::make($password),
                    'is_admin' => true,
                ]);
            });

            Log::info('Updating application status');

            // Update application status
            $application->update([
                'status' => 'approved',
                'domain' => $domain,
            ]);

            Log::info('Preparing approval email');

            // Update email content to use the correct port number
            $domainUrl = 'http://' . $domain . '.localhost:8000';
            $emailContent = "
                <h2>Congratulations! Your Tenant Application is Approved</h2>
                <p>Dear {$application->first_name},</p>
                <p>Your tenant application has been approved. Here are your login credentials:</p>
                <ul>
                    <li><strong>Domain:</strong> {$domainUrl}</li>
                    <li><strong>Email:</strong> {$application->email}</li>
                    <li><strong>Password:</strong> {$password}</li>
                </ul>
                <p>Please change your password after your first login.</p>
                <p><a href='{$domainUrl}'>Click here to access your domain</a></p>
                <br>
                <p>Best regards,<br>Your Multi-Tenant Team</p>
            ";

            Log::info('Sending approval email', ['to' => $application->email]);

            $this->gmailService->sendEmail(
                $application->email,
                'Tenant Application Approved - Your Login Credentials',
                $emailContent
            );

            DB::commit();
            Log::info('Approval process completed successfully');

            return back()->with('success', "Application approved! Credentials have been sent to {$application->email}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in approval process', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // If we failed after creating the tenant, we should still show success but note the email failure
            if (isset($tenant)) {
                return back()->with('success', "Application approved but failed to send email. Error: " . $e->getMessage());
            }

            return back()->with('error', "Failed to approve application. Error: " . $e->getMessage());
        }
    }

    public function reject(Request $request, TenantApplication $application)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        $application->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        // Send rejection email
        $emailContent = "
            <h2>Tenant Application Status Update</h2>
            <p>Dear {$application->first_name},</p>
            <p>We regret to inform you that your tenant application has been declined.</p>
            <p><strong>Reason:</strong> {$validated['rejection_reason']}</p>
            <p>If you have any questions, please feel free to contact us.</p>
            <br>
            <p>Best regards,<br>Your Multi-Tenant Team</p>
        ";

        try {
            $this->gmailService->sendEmail(
                $application->email,
                'Tenant Application Status Update',
                $emailContent
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send rejection email: ' . $e->getMessage());
        }

        return back()->with('success', 'Application rejected successfully.');
    }
} 