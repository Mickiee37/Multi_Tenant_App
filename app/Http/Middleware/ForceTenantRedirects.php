<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForceTenantRedirects
{
    public function handle(Request $request, Closure $next)
    {
        if (!tenant()) {
            return $next($request);
        }

        // Get the current path
        $path = $request->path();
        
        // Don't redirect if already on the correct paths
        if (in_array($path, ['login', 'admin/tenant-dashboard'])) {
            return $next($request);
        }

        // Handle authentication redirects
        if (!Auth::check()) {
            if ($path !== 'login' && !$request->is('login/*')) {
                return redirect('/login');
            }
            return $next($request);
        }

        // Redirect dashboard to tenant dashboard
        if ($path === 'dashboard' || $path === '/') {
            return redirect('/admin/tenant-dashboard');
        }

        return $next($request);
    }
} 