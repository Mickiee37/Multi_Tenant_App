<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

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
        
        // Store the current session ID
        $sessionId = Session::getId();

        // Get tenant database from session using fully qualified table name
        $tenantDatabase = DB::table($mainDatabase . '.sessions')
            ->where('id', $sessionId)
            ->value('tenant_database');

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