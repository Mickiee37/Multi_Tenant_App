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
        'api/*'
    ];

    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        Log::info('TenantMiddleware processing request', [
            'path' => $request->path(),
            'host' => $host,
            'session_id' => session()->getId(),
            'is_authenticated' => auth()->check(),
            'tenant_id' => session('tenant_id')
        ]);

        // Allow public paths without tenant check
        foreach ($this->publicPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }
        
        // Check if we're on a tenant domain
        if (!tenant()) {
            Log::error('Tenant not initialized for domain: ' . $host);
            return redirect()->route('login');
        }

        // For non-public routes, require authentication
        if (!auth()->check()) {
            Log::info('Unauthenticated access attempt', [
                'path' => $request->path(),
                'session_id' => session()->getId(),
                'tenant_id' => session('tenant_id')
            ]);
            
            // Store the intended URL in the session
            $request->session()->put('url.intended', $request->fullUrl());
            
            return redirect()->route('login');
        }

        // Verify tenant session consistency
        if (!session('tenant_id')) {
            Log::error('Missing tenant session data', [
                'path' => $request->path(),
                'session_id' => session()->getId(),
                'auth_id' => auth()->id()
            ]);
            
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login');
        }

        Log::info('TenantMiddleware allowing request', [
            'path' => $request->path(),
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'tenant_id' => session('tenant_id')
        ]);

        return $next($request);
    }
} 