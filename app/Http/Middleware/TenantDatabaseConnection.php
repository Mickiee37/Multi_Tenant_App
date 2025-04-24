<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use App\Models\Tenant;

class TenantDatabaseConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get the main database name from config
        $mainDatabase = config('database.connections.mysql.database');
        
        // Force the session configuration to use main database
        Config::set('session.connection', 'mysql');
        Config::set('session.driver', 'database');
        Config::set('session.table', $mainDatabase . '.sessions');

        // First try to get tenant from domain
        $host = $request->getHost();
        $domain = str_replace('.localhost:8000', '', $host);
        $domain = str_replace('.localhost', '', $domain);
        
        // Find tenant by domain
        $tenant = null;
        try {
            DB::setDefaultConnection('mysql');
            $tenant = DB::table('tenants')->where('domain', $domain)->first();
        } catch (\Exception $e) {
            \Log::error('Error finding tenant', ['error' => $e->getMessage(), 'domain' => $domain]);
        }

        $tenantDatabase = null;
        if ($tenant) {
            $tenantDatabase = $tenant->database;
            
            // Store tenant database in session
            $sessionId = Session::getId();
            try {
                DB::table($mainDatabase . '.sessions')
                    ->where('id', $sessionId)
                    ->update(['tenant_database' => $tenantDatabase]);
            } catch (\Exception $e) {
                \Log::error('Error storing tenant in session', ['error' => $e->getMessage()]);
            }
        } else {
            // If no tenant found by domain, try session as fallback
            $sessionId = Session::getId();
            try {
                $tenantDatabase = DB::table($mainDatabase . '.sessions')
                    ->where('id', $sessionId)
                    ->value('tenant_database');
            } catch (\Exception $e) {
                \Log::error('Error getting tenant from session', ['error' => $e->getMessage()]);
            }
        }

        if ($tenantDatabase) {
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

            // Switch to tenant connection for non-session operations
            DB::purge('tenant');
            DB::reconnect('tenant');
            Config::set('database.default', 'tenant');
            DB::setDefaultConnection('tenant');
        }

        try {
            // Before executing the request, ensure session operations use main database
            DB::statement("USE `{$mainDatabase}`");
            
            // Execute the request
            $response = $next($request);
            
            // After request, switch back to main database for session operations
            DB::statement("USE `{$mainDatabase}`");
            
            return $response;
        } catch (\Exception $e) {
            // On error, ensure we're using main database
            DB::statement("USE `{$mainDatabase}`");
            throw $e;
        } finally {
            // Always ensure we end up using the main database
            DB::statement("USE `{$mainDatabase}`");
            Config::set('database.default', 'mysql');
            DB::setDefaultConnection('mysql');
        }
    }
} 