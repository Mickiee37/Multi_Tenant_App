<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'name',
        'domain',
        'database',
        'database_name',
        'data'
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'domain',
            'database',
            'database_name',
            'data'
        ];
    }

    protected $casts = [
        'data' => 'array'
    ];

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function tenantApplication()
    {
        return $this->hasOne(TenantApplication::class, 'database_name', 'database');
    }
} 