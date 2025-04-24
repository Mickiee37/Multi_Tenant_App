<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        // Check if we're on a tenant domain (must have .localhost in it)
        // Central domain (localhost:8000) should be denied
        if (strpos($host, '.localhost') === false) {
            abort(403, 'Access denied. This section is only accessible from tenant domains.');
        }

        return $next($request);
    }
} 