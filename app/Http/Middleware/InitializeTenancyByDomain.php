<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;

class InitializeTenancyByDomain
{
    protected $tenancy;
    protected $resolver;

    public function __construct(Tenancy $tenancy, DomainTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    public function handle(Request $request, Closure $next)
    {
        $domain = $request->getHost();
        
        // Skip for central domains
        $centralDomains = config('tenancy.central_domains', []);
        if (in_array($domain, $centralDomains)) {
            return $next($request);
        }

        // Try different domain variants
        $domainVariants = [
            $domain, // Original domain with port
            explode(':', $domain)[0], // Domain without port
            'www.' . $domain, // www variant with port
            'www.' . explode(':', $domain)[0] // www variant without port
        ];

        foreach ($domainVariants as $variant) {
            try {
                if ($tenant = $this->resolver->resolve($variant)) {
                    $this->tenancy->initialize($tenant);
                    session(['tenant_id' => $tenant->id]);
                    return $next($request);
                }
            } catch (\Exception $e) {
                Log::debug('Tenant resolution attempt failed for variant: ' . $variant, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        // If we reach here, no tenant was found for any variant
        Log::error('No tenant found for domain variants', [
            'domain' => $domain,
            'variants_tried' => $domainVariants
        ]);

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        abort(404, 'Tenant not found');
    }
} 