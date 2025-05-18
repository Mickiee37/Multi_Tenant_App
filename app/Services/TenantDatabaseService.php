<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use PDO;

class TenantDatabaseService
{
    protected $requiredTables = [
        'users',
        'password_reset_tokens',
        'migrations',
        'failed_jobs',
        'personal_access_tokens',
        'products'
    ];

    public function createDatabase($tenantId, $databaseName)
    {
        try {
            // Force using root connection
            $rootPdo = new PDO(
                'mysql:host=' . config('database.connections.mysql.host'),
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Check if database exists first
            $result = $rootPdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'");
            $exists = $result->fetch();

            if (!$exists) {
                // Create new database only if it doesn't exist
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
                
                // Verify database was created
                $result = $rootPdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$databaseName}'");
                if (!$result->fetch()) {
                    throw new \Exception("Failed to create database {$databaseName}");
                }
            }

            // Configure tenant connection
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => config('database.connections.mysql.host'),
                'database' => $databaseName,
                'username' => config('database.connections.mysql.username'),
                'password' => config('database.connections.mysql.password'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);

            // Clear any cached configuration
            DB::purge('tenant');
            DB::disconnect('tenant');

            // Connect to the new database
            DB::reconnect('tenant');

            // Create other necessary tables
            $this->createTenantTables();

            // Verify tables were created
            $this->verifyTables();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to create tenant database', [
                'tenant_id' => $tenantId,
                'database' => $databaseName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function verifyTables()
    {
        foreach ($this->requiredTables as $table) {
            if (!Schema::connection('tenant')->hasTable($table)) {
                $this->createSpecificTable($table);
                
                // Verify table was created
                if (!Schema::connection('tenant')->hasTable($table)) {
                    \Log::error("Failed to create table {$table}");
                    throw new \Exception("Failed to create table: {$table}");
                }
            }
        }
    }

    protected function createSpecificTable($tableName)
    {
        try {
            switch ($tableName) {
                case 'users':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `users` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `email` varchar(255) NOT NULL,
                            `email_verified_at` timestamp NULL DEFAULT NULL,
                            `password` varchar(255) NOT NULL,
                            `is_admin` tinyint(1) NOT NULL DEFAULT '0',
                            `remember_token` varchar(100) DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `users_email_unique` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;

                case 'password_reset_tokens':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
                            `email` varchar(255) NOT NULL,
                            `token` varchar(255) NOT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;

                case 'migrations':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `migrations` (
                            `id` int unsigned NOT NULL AUTO_INCREMENT,
                            `migration` varchar(255) NOT NULL,
                            `batch` int NOT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;

                case 'failed_jobs':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `failed_jobs` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `uuid` varchar(255) NOT NULL,
                            `connection` text NOT NULL,
                            `queue` text NOT NULL,
                            `payload` longtext NOT NULL,
                            `exception` longtext NOT NULL,
                            `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;

                case 'personal_access_tokens':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `tokenable_type` varchar(255) NOT NULL,
                            `tokenable_id` bigint unsigned NOT NULL,
                            `name` varchar(255) NOT NULL,
                            `token` varchar(64) NOT NULL,
                            `abilities` text,
                            `last_used_at` timestamp NULL DEFAULT NULL,
                            `expires_at` timestamp NULL DEFAULT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`),
                            UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
                            KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;

                case 'products':
                    DB::connection('tenant')->unprepared("
                        CREATE TABLE IF NOT EXISTS `products` (
                            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                            `name` varchar(255) NOT NULL,
                            `description` text,
                            `price` decimal(10,2) NOT NULL,
                            `created_at` timestamp NULL DEFAULT NULL,
                            `updated_at` timestamp NULL DEFAULT NULL,
                            PRIMARY KEY (`id`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    break;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to create table {$tableName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function createTenantTables()
    {
        try {
            // Create each required table
            foreach ($this->requiredTables as $table) {
                if (!Schema::connection('tenant')->hasTable($table)) {
                    $this->createSpecificTable($table);
                    
                    // Verify table was created
                    if (!Schema::connection('tenant')->hasTable($table)) {
                        throw new \Exception("Failed to create table: {$table}");
                    }
                }
            }

            // Verify all required tables exist
            $missingTables = array_filter($this->requiredTables, function($table) {
                return !Schema::connection('tenant')->hasTable($table);
            });

            if (!empty($missingTables)) {
                throw new \Exception('Missing required tables: ' . implode(', ', $missingTables));
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to create tenant tables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function createBackup($database)
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "{$database}_{$timestamp}.sql";
        $path = storage_path("app/backups/{$filename}");

        // Ensure backups directory exists
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Get database configuration
        $host = config('database.connections.mysql.host');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        // Create backup using mysqldump
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            $host,
            $username,
            $password,
            $database,
            $path
        );

        exec($command);

        return $filename;
    }

    private function createTables(string $database): void
    {
        DB::statement("USE `{$database}`");

        // Create users table
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `email_verified_at` timestamp NULL DEFAULT NULL,
                `password` varchar(255) NOT NULL,
                `is_admin` tinyint(1) NOT NULL DEFAULT '0',
                `remember_token` varchar(100) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `users_email_unique` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Create products table
        DB::unprepared("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `price` decimal(10,2) NOT NULL,
                `description` text,
                `image` varchar(255) NOT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}