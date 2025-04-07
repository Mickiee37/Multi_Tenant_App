<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

return [
    'storage_driver' => 'db',
    'storage' => [
        'db' => [
            'connection' => null,
            'table_names' => [
                'tenants' => 'tenants',
                'domains' => 'domains',
            ],
        ],
    ],

    'tenant_model' => \App\Models\Tenant::class,
    'domain_model' => Domain::class,

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
        'middleware' => [
            // See https://tenancyforlaravel.com/docs/v3/configuration/#middleware
        ],
    ],

    'redis' => [
        'prefix_base' => 'tenant',
        'prefixed_connections' => [
            // 'default',
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
            // 's3',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        'url_override' => [
            'public' => '%storage_url%/app/public/',
        ],
    ],

    'central_domains' => [
        'localhost'
    ],

    'identification' => [
        'domain' => [
            'central_domains' => [
                'localhost',
            ],
        ],
    ],
];
