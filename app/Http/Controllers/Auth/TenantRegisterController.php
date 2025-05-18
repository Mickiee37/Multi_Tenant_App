<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TenantProvisionService;

class TenantRegisterController extends Controller
{
    protected $tenantService;

    public function __construct(TenantProvisionService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
        ]);

        // Delegate the tenant registration to the service
        $this->tenantService->registerTenant($request);

        return response()->json(['message' => 'Tenant registered successfully.']);
    }
}

