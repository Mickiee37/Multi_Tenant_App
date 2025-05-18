<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

return [
    'tenant_model' => \App\Models\Tenant::class,
    'domain_model' => \App\Models\Domain::class,

    'central_domains' => [
        'localhost',
        'localhost:8000',
        '127.0.0.1',
        '127.0.0.1:8000'
    ],

    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'middleware' => ['web', 'universal'],
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Note: phpredis is required
    ],

    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        // Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
    ],

    'middleware_priority' => [
        'first' => [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ],
        'last' => [
            \Stancl\Tenancy\Middleware\InitializeTenancyByRequestData::class,
        ],
    ],

    'storage_driver' => 'db',

    'storage' => [
        'db' => [
            'table_names' => [
                'tenants' => 'tenants',
                'domains' => 'domains',
            ],
        ],
    ],

    'tenant_route_namespace' => 'App\Http\Controllers',
];
