<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use App\Models\Tenant;  // Assuming you have a Tenant model

class SetTenantDatabase
{
    public function handle($request, Closure $next)
    {
        // Logic to determine the tenant (e.g., based on subdomain or request)
        $tenant = Tenant::where('subdomain', request()->getHost())->first();
        
        if ($tenant) {
            $tenantDb = [
                'driver' => 'mysql',
                'host' => env('DB_TENANT_HOST'),
                'port' => env('DB_TENANT_PORT'),
                'database' => $tenant->database,
                'username' => env('DB_TENANT_USERNAME'),
                'password' => env('DB_TENANT_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];

            Config::set('database.connections.tenant', $tenantDb);
        }

        return $next($request);
    }
}

