<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the subscription upgrade page.
     *
     * @return \Illuminate\View\Response
     */
    public function showUpgradePage(Request $request)
    {
        $tenant = $this->getTenantFromDomain();
        
        if (!$tenant) {
            return redirect()->back()->with('error', 'Tenant not found');
        }
        
        $currentPlan = $tenant->subscription_plan ?? 'basic';
        $plans = [
            'basic' => [
                'name' => 'Basic',
                'productLimit' => 2,
                'monthlyPrice' => 9.99,
                'features' => [
                    'Basic product management',
                    '2 products',
                    'Standard support'
                ]
            ],
            'pro' => [
                'name' => 'Pro',
                'productLimit' => 5,
                'monthlyPrice' => 19.99,
                'features' => [
                    'Advanced product management',
                    '5 products',
                    'Theme customization (2 themes)',
                ]
            ],
            'premium' => [
                'name' => 'Premium',
                'productLimit' => 10,
                'monthlyPrice' => 39.99,
                'features' => [
                    'Full product management suite',
                    '10 products',
                    'Theme customization (5 themes)',
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'productLimit' => 20,
                'monthlyPrice' => 79.99,
                'features' => [
                    'Complete product management solution',
                    '20 products',
                    'Dedicated support',
                    'Full analytics suite',
                    'API access',
                    'Custom integrations',
                    'White labeling'
                ]
            ]
        ];
        
        return view('tenant.subscription.upgrade', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'plans' => $plans
        ]);
    }
    
    /**
     * Upgrade the tenant's subscription plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function upgrade(Request $request)
    {
        try {
            Log::info('Subscription upgrade request received', [
                'host' => request()->getHost(),
                'plan' => $request->input('plan', 'not set'),
                'all_data' => $request->all()
            ]);
            
            $request->validate([
                'plan' => 'required|in:basic,pro,premium,enterprise',
            ]);
            
            $tenant = $this->getTenantFromDomain();
            
            if (!$tenant) {
                Log::error('Tenant not found for domain', ['domain' => request()->getHost()]);
                return redirect()->back()->with('error', 'Tenant not found');
            }
            
            $newPlan = $request->input('plan');
            $oldPlan = $tenant->subscription_plan ?? 'basic';
            
            // In a real application, you would process payment here
            // For this example, we'll just update the plan
            
            // Update tenant's subscription plan
            $tenant->subscription_plan = $newPlan;
            // Set expiration 1 year from now
            $tenant->subscription_expires_at = now()->addYear();
            $tenant->save();
            
            Log::info('Tenant subscription upgraded', [
                'tenant' => $tenant->id,
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan
            ]);
            
            // Simply redirect back after successful upgrade
            return redirect()->back()
                ->with('status', "Subscription upgraded to {$newPlan} plan successfully!");
        } catch (\Exception $e) {
            Log::error('Failed to upgrade subscription', [
                'tenant' => $tenant->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to upgrade subscription: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the tenant for the current domain.
     *
     * @return \App\Models\Tenant|null
     */
    private function getTenantFromDomain()
    {
        $host = request()->getHost();
        
        return Tenant::where('domain', $host)->first();
    }
}
