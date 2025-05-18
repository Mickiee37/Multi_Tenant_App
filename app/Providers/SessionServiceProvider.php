<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Get the main database name
        $mainDatabase = config('database.connections.mysql.database');
        
        // Force sessions to always use the main database connection
        Config::set('session.connection', 'mysql');
        Config::set('session.driver', 'database');
        Config::set('session.table', $mainDatabase . '.sessions');
        
        // Ensure we're using the main database
        DB::statement("USE `{$mainDatabase}`");

        // Ensure the sessions table exists in the main database
        if (!$this->sessionTableExists($mainDatabase)) {
            $this->createSessionTable($mainDatabase);
        }
    }

    protected function sessionTableExists($database)
    {
        try {
            $result = DB::select("
                SELECT COUNT(*) as table_exists 
                FROM information_schema.tables 
                WHERE table_schema = ? 
                AND table_name = 'sessions'", [$database]);
            return $result[0]->table_exists > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function createSessionTable($database)
    {
        try {
            DB::statement("USE `{$database}`");
            DB::unprepared("
                CREATE TABLE IF NOT EXISTS `{$database}`.`sessions` (
                    `id` varchar(255) NOT NULL,
                    `user_id` bigint unsigned DEFAULT NULL,
                    `ip_address` varchar(45) DEFAULT NULL,
                    `user_agent` text,
                    `payload` longtext NOT NULL,
                    `last_activity` int NOT NULL,
                    `tenant_database` varchar(255) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `sessions_user_id_index` (`user_id`),
                    KEY `sessions_last_activity_index` (`last_activity`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (\Exception $e) {
            \Log::error('Failed to create sessions table', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 