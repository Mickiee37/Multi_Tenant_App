<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CentralDomainMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // If tenant() helper returns true, we're on a tenant domain
        if (tenant()) {
            Log::warning('Tenant attempting to access central domain route', [
                'path' => $request->path(),
                'host' => $request->getHost(),
                'tenant_id' => tenant()->id
            ]);
            
            // Redirect tenant users to their dashboard
            return redirect('/admin/tenant-dashboard');
        }

        return $next($request);
    }
} 