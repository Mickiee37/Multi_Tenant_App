<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CentralDomainMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', [
            'localhost',
            'localhost:8000',
            '127.0.0.1',
            '127.0.0.1:8000'
        ]);

        // If this is not a central domain, block access
        if (!in_array($host, $centralDomains)) {
            Log::warning('Non-central domain attempted to access central route', [
                'path' => $request->path(),
                'host' => $host
            ]);
            
            // Redirect to tenant dashboard if it's a tenant
            if (tenant()) {
                return redirect('/admin/tenant-dashboard')
                    ->with('error', 'Access denied. This section is only accessible from the central domain.');
            }
            
            abort(404, 'Not Found');
        }

        return $next($request);
    }
} 