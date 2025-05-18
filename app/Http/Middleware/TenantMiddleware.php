<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    protected $publicPaths = [
        'login',
        'logout',
        '_ignition/*',
        'livewire/*',
        'sanctum/*',
        'api/*',
        'assets/*',
        'css/*',
        'js/*'
    ];

    protected $restrictedPaths = [
        'admin/users*',
        'admin/settings*',
        'admin/roles*'
    ];

    protected $allowedPaths = [
        'admin/tenant-dashboard',
        'admin/tenant-dashboard/*',
        'admin/profile',
        'admin/profile/*',
        'admin/products*'
    ];

    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $path = $request->path();
        
        Log::info('TenantMiddleware processing request', [
            'path' => $path,
            'host' => $host,
            'session_id' => session()->getId(),
            'is_authenticated' => auth()->check(),
            'tenant_id' => session('tenant_id')
        ]);

        // Check if we're on a tenant domain
        if (tenant()) {
            // Allow public paths without any checks
            foreach ($this->publicPaths as $publicPath) {
                if ($request->is($publicPath)) {
                    return $next($request);
                }
            }

            // Check if path is explicitly allowed for tenants
            foreach ($this->allowedPaths as $allowedPath) {
                if ($request->is($allowedPath)) {
                    // If authenticated, allow access
                    if (auth()->check()) {
                        return $next($request);
                    }
                    // If not authenticated, redirect to login
                    return redirect()->route('login');
                }
            }

            // Handle restricted paths
            foreach ($this->restrictedPaths as $restrictedPath) {
                if (fnmatch($restrictedPath, $path)) {
                    Log::warning('Tenant attempted to access restricted path', [
                        'path' => $path,
                        'tenant_id' => tenant()->id,
                        'user_id' => auth()->id()
                    ]);
                    return redirect('/admin/tenant-dashboard');
                }
            }

            // For any other routes, require authentication
            if (!auth()->check()) {
                return redirect()->route('login');
            }

            // Verify tenant session consistency
            if (!session('tenant_id')) {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                return redirect()->route('login');
            }

            return $next($request);
        }

        // Not a tenant domain, proceed normally
        return $next($request);
    }
} 