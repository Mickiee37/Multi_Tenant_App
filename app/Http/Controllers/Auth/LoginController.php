<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/admin/tenant-dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm(Request $request)
    {
        if (tenant()) {
            Log::info('Showing tenant login form', [
                'host' => $request->getHost(),
                'tenant_id' => session('tenant_id'),
                'is_authenticated' => Auth::check()
            ]);

            if (Auth::check()) {
                return redirect()->route('tenant.admin.dashboard');
            }
        } else {
            if (Auth::check()) {
                return redirect('/dashboard');
            }
        }

        return view('auth.login');
    }

    protected function attemptLogin(Request $request)
    {
        try {
            Log::info('Attempting login', [
                'email' => $request->email,
                'tenant' => tenant() ? tenant()->id : 'none',
                'host' => $request->getHost()
            ]);

            if (tenant()) {
                // For tenant domains, attempt login against tenant database
                DB::purge('tenant');
                DB::reconnect('tenant');
                
                $success = Auth::guard('web')->attempt(
                    $this->credentials($request),
                    $request->boolean('remember')
                );

                if ($success) {
                    session(['tenant_id' => tenant()->id]);
                    session(['tenant_database' => tenant()->database]);
                }

                return $success;
            }

            // For central domain, use default connection
            return Auth::guard('web')->attempt(
                $this->credentials($request),
                $request->boolean('remember')
            );
        } catch (\Exception $e) {
            Log::error('Login attempt failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function authenticated(Request $request, $user)
    {
        Log::info('User authenticated', [
            'user' => $user->id,
            'email' => $user->email,
            'host' => $request->getHost(),
            'tenant' => tenant() ? tenant()->id : 'none',
            'session_id' => session()->getId()
        ]);

        if (tenant()) {
            // For tenant domains, always redirect to tenant dashboard
            return redirect('/admin/tenant-dashboard');
        }

        // For central domain
        if ($user->is_admin) {
            return redirect('/admin/dashboard');
        }
        
        return redirect($this->redirectTo);
    }

    public function logout(Request $request)
    {
        Log::info('User logging out', [
            'user_id' => Auth::id(),
            'tenant' => tenant() ? tenant()->id : 'none'
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Clear tenant-specific session data
        $request->session()->forget(['tenant_id', 'tenant_database']);

        return redirect()->route('login');
    }

    protected function guard()
    {
        return Auth::guard('web');
    }
} 