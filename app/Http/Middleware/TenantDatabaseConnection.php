<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;

class TenantDatabaseConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the main database name from config
        $mainDatabase = config('database.connections.mysql.database');
        
        \Log::info('TenantDatabaseConnection middleware starting', [
            'request_path' => $request->path(),
            'main_database' => $mainDatabase,
            'is_authenticated' => Auth::check(),
            'session_id' => session()->getId()
        ]);

        // Parse domain from host
        $host = $request->getHost();
        $domain = $host;
        
        // Skip tenant lookup for specific routes
        if ($request->is('login') || $request->is('logout') || $request->is('_ignition/*')) {
            return $next($request);
        }
        
        // Find tenant by domain
        $tenant = null;
        try {
            // Always use main connection for tenant lookup
            DB::setDefaultConnection('mysql');
            
            // Try to find tenant by exact domain
            $tenant = DB::table('tenants')
                ->where('domain', $domain)
                ->first();
            
            \Log::info('Tenant lookup result', [
                'found' => $tenant !== null,
                'tenant_id' => $tenant ? $tenant->id : null,
                'database' => $tenant ? $tenant->database : null,
                'searched_domain' => $domain,
                'session_id' => session()->getId()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error finding tenant', [
                'error' => $e->getMessage(),
                'domain' => $domain,
                'host' => $host
            ]);
            return $next($request);
        }

        if ($tenant) {
            $tenantDatabase = $tenant->database;
            
            // Store tenant info in session
            $request->session()->put('tenant_id', $tenant->id);
            $request->session()->put('tenant_database', $tenantDatabase);
            
            \Log::info('Configuring tenant connection', [
                'database' => $tenantDatabase,
                'session_id' => session()->getId(),
                'tenant_id' => $tenant->id
            ]);
            
            // Configure tenant connection
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'database' => $tenantDatabase,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);

            // Switch to tenant connection
            DB::purge('tenant');
            DB::reconnect('tenant');
            
            // Configure session to use main database
            Config::set('session.connection', 'mysql');
            Config::set('session.driver', 'database');
            Config::set('session.table', $mainDatabase . '.sessions');
            
            // Set auth provider to use tenant connection
            Config::set('auth.providers.users.connection', 'tenant');
            
            \Log::info('Tenant connection configured', [
                'database' => $tenantDatabase,
                'auth_connection' => config('auth.providers.users.connection'),
                'session_connection' => config('session.connection'),
                'session_id' => session()->getId()
            ]);

            // Share tenant information with all views
            view()->share('current_tenant', $tenant);
        }

        try {
            $response = $next($request);
            
            // Log redirect information if present
            if ($response->headers->has('Location')) {
                \Log::info('Redirect detected', [
                    'from' => $request->url(),
                    'to' => $response->headers->get('Location'),
                    'session_id' => session()->getId(),
                    'tenant_id' => session('tenant_id')
                ]);
            }
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('Error in middleware', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId()
            ]);
            throw $e;
        } finally {
            // Reset connections for next request
            if (tenant()) {
                DB::purge('tenant');
            }
            DB::purge('mysql');
            DB::setDefaultConnection('mysql');
        }
    }
} 