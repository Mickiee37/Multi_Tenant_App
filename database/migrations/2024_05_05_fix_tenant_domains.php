<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixTenantDomains20240505 extends Migration
{
    public function up()
    {
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
        }
    }

    public function down()
    {
        // No rollback needed as this is a data fix
    }
} 