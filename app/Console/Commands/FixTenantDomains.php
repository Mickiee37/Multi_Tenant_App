<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixTenantDomains extends Command
{
    protected $signature = 'tenants:fix-domains';
    protected $description = 'Fix tenant domains to use consistent format';

    public function handle()
    {
        $this->info('Starting to fix tenant domains...');

        // Update tenants table
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $domain = preg_replace(['/:\d+$/', '/\.localhost$/'], '', $tenant->domain);
            $newDomain = $domain . '.localhost';
            
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['domain' => $newDomain]);
                
            // Update or create matching domain record
            DB::table('domains')
                ->updateOrInsert(
                    ['tenant_id' => $tenant->id],
                    [
                        'domain' => $newDomain,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                
            $this->info("Updated tenant {$tenant->id} domain to {$newDomain}");
        }

        $this->info('Tenant domains have been fixed.');
    }
} 