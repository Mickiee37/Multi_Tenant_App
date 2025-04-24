<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CentralDomainMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        
        // Check if we're on the central domain (should be just localhost:8000)
        // If there's a subdomain (like kanga.localhost), deny access
        if (strpos($host, '.localhost') !== false) {
            abort(403, 'Access denied. This section is only accessible from the central domain.');
        }

        return $next($request);
    }
} 