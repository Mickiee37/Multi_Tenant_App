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

class AdminController extends Controller
{
    protected $tenantDatabaseService;
    protected $gmailClient;

    public function __construct(TenantDatabaseService $tenantDatabaseService, Client $gmailClient)
    {
        $this->tenantDatabaseService = $tenantDatabaseService;
        $this->gmailClient = $gmailClient;
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
        $applications = TenantApplication::orderBy('created_at', 'desc')->get();
        return view('admin.tenant-applications', compact('applications'));
    }

    public function approveTenantApplication($id)
    {
        try {
            DB::beginTransaction();

            $application = TenantApplication::findOrFail($id);
            
            if ($application->status !== 'pending') {
                return back()->with('error', 'This application has already been processed.');
            }

            // Create the tenant database
            $this->tenantDatabaseService->createDatabase($id, $application->database_name);

            // Update application status
            $application->status = 'approved';
            $application->save();

            // Send approval email
            $this->sendApprovalEmail($application);

            DB::commit();

            return back()->with('success', 'Tenant application approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve tenant application: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve tenant application. Please try again.');
        }
    }

    public function rejectTenantApplication(Request $request, $id)
    {
        try {
            $application = TenantApplication::findOrFail($id);
            
            if ($application->status !== 'pending') {
                return back()->with('error', 'This application has already been processed.');
            }

            $application->status = 'rejected';
            $application->rejection_reason = $request->input('rejection_reason');
            $application->save();

            // Send rejection email
            $this->sendRejectionEmail($application);

            return back()->with('success', 'Tenant application rejected successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to reject tenant application: ' . $e->getMessage());
            return back()->with('error', 'Failed to reject tenant application. Please try again.');
        }
    }

    protected function sendApprovalEmail($application)
    {
        try {
            $message = new Message();
            
            $rawEmail = "From: admin@example.com\r\n";
            $rawEmail .= "To: {$application->email}\r\n";
            $rawEmail .= "Subject: Your Tenant Application Has Been Approved\r\n\r\n";
            $rawEmail .= "Dear {$application->first_name} {$application->last_name},\n\n";
            $rawEmail .= "We are pleased to inform you that your tenant application has been approved.\n";
            $rawEmail .= "You can now access your tenant space at: {$application->domain}\n\n";
            $rawEmail .= "Best regards,\nThe Admin Team";

            $message->setRaw(base64_encode($rawEmail));
            
            $this->gmailClient->getService(Gmail::class)->users_messages->send('me', $message);
        } catch (\Exception $e) {
            Log::error('Failed to send approval email: ' . $e->getMessage());
        }
    }

    protected function sendRejectionEmail($application)
    {
        try {
            $message = new Message();
            
            $rawEmail = "From: admin@example.com\r\n";
            $rawEmail .= "To: {$application->email}\r\n";
            $rawEmail .= "Subject: Update on Your Tenant Application\r\n\r\n";
            $rawEmail .= "Dear {$application->first_name} {$application->last_name},\n\n";
            $rawEmail .= "We regret to inform you that your tenant application has been rejected.\n";
            if ($application->rejection_reason) {
                $rawEmail .= "Reason: {$application->rejection_reason}\n\n";
            }
            $rawEmail .= "If you have any questions, please feel free to contact us.\n\n";
            $rawEmail .= "Best regards,\nThe Admin Team";

            $message->setRaw(base64_encode($rawEmail));
            
            $this->gmailClient->getService(Gmail::class)->users_messages->send('me', $message);
        } catch (\Exception $e) {
            Log::error('Failed to send rejection email: ' . $e->getMessage());
        }
    }
} 