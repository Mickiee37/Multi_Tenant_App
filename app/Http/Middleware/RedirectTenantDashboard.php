<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Providers\RouteServiceProvider;

class RedirectTenantDashboard
{
    public function handle(Request $request, Closure $next)
    {
        // If we're on a tenant domain and authenticated
        if (tenant() && Auth::check()) {
            // List of paths that should redirect to tenant dashboard
            $redirectPaths = ['dashboard', '/', 'home'];
            
            // Get the current path without leading/trailing slashes
            $currentPath = trim($request->path(), '/');
            
            // If current path is in our redirect list, force redirect to tenant dashboard
            if (in_array($currentPath, $redirectPaths)) {
                return redirect('/admin/tenant-dashboard');
            }
        }

        return $next($request);
    }
} 