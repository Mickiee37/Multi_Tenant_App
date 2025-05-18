<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($this->attemptLogin($request)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user = Auth::user();
            return $this->authenticated($request, $user);
        }

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        try {
            if (tenant()) {
                Log::info('Tenant authenticated, redirecting to tenant dashboard', [
                    'tenant_id' => tenant()->id,
                    'user_id' => $user->id,
                ]);
                return redirect('/admin/tenant-dashboard');
            }
        } catch (\Exception $e) {
            Log::error('Error in authentication redirect', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return redirect($this->redirectPath());
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            try {
                if (tenant()) {
                    return redirect('/admin/tenant-dashboard');
                }
            } catch (\Exception $e) {
                Log::error('Error checking tenant in showLoginForm', [
                    'error' => $e->getMessage()
                ]);
            }
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        try {
            $isTenant = tenant() ? true : false;
        } catch (\Exception $e) {
            $isTenant = false;
            Log::error('Error in logout redirect', [
                'error' => $e->getMessage()
            ]);
        }

        return redirect($isTenant ? '/login' : '/');
    }

    /**
     * Get the post login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        try {
            if (tenant()) {
                Log::info('Tenant redirect path requested, returning tenant dashboard');
                return '/admin/tenant-dashboard';
            }
        } catch (\Exception $e) {
            Log::error('Error in redirect path', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return RouteServiceProvider::HOME;
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only('email', 'password');
    }

    protected function attemptLogin(Request $request)
    {
        try {
            $host = $request->getHost();
            Log::info('Login attempt', ['host' => $host]);
            
            if (strpos($host, '.localhost') !== false) {
                $domain = explode('.', $host)[0];
                Log::info('Tenant domain detected', ['domain' => $domain]);
                
                // Check if this is a valid tenant
                $tenant = DB::table('tenants')->where('domain', $domain.'.localhost')->first();
                if ($tenant) {
                    Log::info('Tenant found', ['tenant' => $tenant->id]);
                    
                    // Configure tenant connection
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::reconnect('tenant');
                    
                    // Store tenant info in session
                    if ($request->hasSession()) {
                        session(['tenant_id' => $tenant->id]);
                        session(['tenant_domain' => $tenant->domain]);
                        session(['tenant_database' => $tenant->database]);
                    }
                }
            }

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
} 