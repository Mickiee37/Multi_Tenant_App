<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Illuminate\Support\Facades\Schema;

class DiagnosticController extends Controller
{
    protected $resolver;

    public function __construct(DomainTenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function tenantCheck(Request $request)
    {
        $domain = $request->getHost();
        $output = [];
        
        $output['requested_host'] = $domain;
        $output['central_domains'] = config('tenancy.central_domains', []);
        $output['is_central'] = in_array($domain, config('tenancy.central_domains', []));
        
        // List all tenants
        try {
            $tenants = DB::table('tenants')->get();
            $output['total_tenants'] = $tenants->count();
            $output['tenants'] = $tenants->map(function($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'db' => $tenant->database
                ];
            });
        } catch (\Exception $e) {
            $output['db_error'] = $e->getMessage();
        }
        
        // Try to resolve tenant for this domain
        try {
            $variants = [
                $domain,
                explode(':', $domain)[0]
            ];
            
            $output['domain_variants'] = $variants;
            $output['tenant_resolved'] = false;
            
            foreach ($variants as $variant) {
                try {
                    $tenant = $this->resolver->resolve($variant);
                    if ($tenant) {
                        $output['tenant_resolved'] = true;
                        $output['resolved_tenant'] = [
                            'id' => $tenant->id,
                            'name' => $tenant->name,
                            'domain' => $tenant->domain,
                            'db' => $tenant->database
                        ];
                        break;
                    }
                } catch (\Exception $e) {
                    $output['resolve_error_'.$variant] = $e->getMessage();
                }
            }
            
            // Check if tenant() global helper works
            try {
                $currentTenant = tenant();
                $output['global_tenant_available'] = $currentTenant ? true : false;
                if ($currentTenant) {
                    $output['global_tenant'] = [
                        'id' => $currentTenant->id,
                        'name' => $currentTenant->name,
                        'domain' => $currentTenant->domain,
                        'db' => $currentTenant->database
                    ];
                }
            } catch (\Exception $e) {
                $output['global_tenant_error'] = $e->getMessage();
            }
            
            // Check if domains table exists
            try {
                $output['domains_table_exists'] = Schema::hasTable('domains');
                if ($output['domains_table_exists']) {
                    $domains = DB::table('domains')->get();
                    $output['domains'] = $domains;
                }
            } catch (\Exception $e) {
                $output['domains_table_error'] = $e->getMessage();
            }
            
        } catch (\Exception $e) {
            $output['resolve_error'] = $e->getMessage();
        }
        
        return response()->json($output);
    }
} 