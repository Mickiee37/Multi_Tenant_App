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
        'data',
        'subscription_plan',
        'subscription_expires_at'
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'domain',
            'database',
            'database_name',
            'data',
            'subscription_plan',
            'subscription_expires_at'
        ];
    }

    protected $casts = [
        'data' => 'array',
        'subscription_expires_at' => 'datetime'
    ];

    public function domains()
    {
        return $this->hasMany(Domain::class);
    }

    public function tenantApplication()
    {
        return $this->hasOne(TenantApplication::class, 'database_name', 'database');
    }
    
    /**
     * Get the maximum number of products allowed based on subscription plan
     */
    public function getProductLimit()
    {
        return match($this->subscription_plan) {
            'pro' => 5,
            'premium' => 10,
            'enterprise' => 20,
            default => 2 // Basic plan
        };
    }
    
    /**
     * Check if the tenant has reached their product limit
     */
    public function hasReachedProductLimit($currentCount)
    {
        return $currentCount >= $this->getProductLimit();
    }
} 