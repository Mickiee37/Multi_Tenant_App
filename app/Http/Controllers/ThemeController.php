<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ThemeController extends Controller
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
     * Update the tenant's theme settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            $request->validate([
                'theme' => 'required|string|in:default,dark,light,blue,green,purple,red,orange,custom',
            ]);
            
            $host = request()->getHost();
            $tenant = Tenant::where('domain', $host)->first();
            
            if (!$tenant) {
                return redirect()->back()->with('error', 'Tenant not found');
            }
            
            // Check if the tenant has access to this theme based on their subscription
            $themesAvailable = $this->getAvailableThemesCount($tenant->subscription_plan ?? 'basic');
            $themeIndex = $this->getThemeIndex($request->input('theme'));
            
            if ($themeIndex > $themesAvailable) {
                return redirect()->back()->with('error', 'Your current subscription plan does not include access to this theme. Please upgrade your plan to access more themes.');
            }
            
            // Get old theme for comparison
            $oldTheme = $tenant->getTheme();
            $newTheme = $request->input('theme');
            
            // Skip update if theme hasn't changed
            if ($oldTheme === $newTheme) {
                return redirect()->back()->with('status', 'No changes to theme were made.');
            }
            
            // Store the theme setting in the tenant's data attribute
            $data = $tenant->data ?? [];
            if (is_string($data)) {
                $data = json_decode($data, true) ?? [];
            }
            
            $data['theme'] = $newTheme;
            $tenant->data = $data;
            $tenant->save();
            
            Log::info('Tenant theme updated', [
                'tenant' => $tenant->id,
                'theme' => $newTheme,
                'old_theme' => $oldTheme,
                'user_agent' => $request->header('User-Agent')
            ]);
            
            // Force a complete reload to a plain URL with a cache-busting parameter
            $cacheBuster = time() . rand(1000, 9999);
            
            // Handle XHR requests differently (return JSON)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Theme updated successfully to " . ucfirst($newTheme) . " theme!",
                    'theme' => $newTheme,
                    'redirect' => url('/admin/tenant-dashboard?theme_updated=1&force_theme=' . $newTheme . '&cache=' . $cacheBuster)
                ]);
            }
            
            // For direct HTML form submissions, redirect with force_theme parameter
            return redirect(url('/admin/tenant-dashboard?theme_updated=1&force_theme=' . $newTheme . '&cache=' . $cacheBuster))
                ->with('status', "Theme updated successfully to " . ucfirst($newTheme) . " theme!")
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        } catch (\Exception $e) {
            Log::error('Failed to update theme', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Failed to update theme: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the number of themes available based on subscription plan.
     *
     * @param  string  $plan
     * @return int
     */
    private function getAvailableThemesCount($plan)
    {
        return match($plan) {
            'pro' => 2,
            'premium' => 5,
            'enterprise' => 10,
            default => 1 // Basic plan
        };
    }
    
    /**
     * Get the index of a theme (used to determine if it's accessible).
     *
     * @param  string  $theme
     * @return int
     */
    private function getThemeIndex($theme)
    {
        return match($theme) {
            'default' => 1,
            'dark', 'light' => 2,
            'blue', 'green' => 3,
            'purple', 'red', 'orange' => 5,
            'custom' => 10,
            default => 1
        };
    }
} 