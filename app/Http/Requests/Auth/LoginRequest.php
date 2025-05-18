<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Get the current host
        $host = request()->getHost();
        
        // Check if this is a central domain
        $centralDomains = config('tenancy.central_domains', ['localhost', '127.0.0.1', 'localhost:8000']);
        $isCentralDomain = in_array($host, $centralDomains);

        Log::info('Authentication attempt details', [
            'host' => $host,
            'is_central_domain' => $isCentralDomain,
            'email' => $this->email,
            'central_domains' => $centralDomains
        ]);

        try {
            if ($isCentralDomain) {
                // Central domain login
                if (!Auth::attempt($this->only('email', 'password'))) {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'email' => 'Invalid credentials.',
                    ]);
                }
            } else {
                // First, check if the tenant exists
                DB::setDefaultConnection('mysql');
                
                // Debug: Log all tenants and their domains
                $allTenants = DB::table('tenants')->get();
                Log::info('All registered tenants:', [
                    'tenants' => $allTenants->map(function($t) {
                        return ['id' => $t->id, 'domain' => $t->domain];
                    })
                ]);
                
                // Try to find tenant by domain, with and without port
                $tenant = DB::table('tenants')
                    ->where('domain', $host)
                    ->first();
                
                Log::info('Tenant lookup details', [
                    'searched_host' => $host,
                    'tenant_found' => !is_null($tenant),
                    'tenant_details' => $tenant,
                    'sql' => DB::getQueryLog()
                ]);

                if (!$tenant) {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'email' => 'Invalid tenant domain. Please make sure you are using the correct URL.',
                    ]);
                }

                // Store tenant database in session
                session(['tenant_database' => $tenant->database]);

                // Configure and switch to tenant database
                Config::set('database.connections.tenant.database', $tenant->database);
                DB::purge('tenant');
                DB::reconnect('tenant');
                DB::setDefaultConnection('tenant');

                Log::info('Database connection configured', [
                    'database' => $tenant->database,
                    'connection' => config('database.connections.tenant')
                ]);

                // Get user from tenant database
                $user = DB::connection('tenant')->table('users')
                    ->where('email', $this->email)
                    ->first();

                Log::info('User lookup result', [
                    'user_found' => !is_null($user),
                    'email' => $this->email,
                    'database' => $tenant->database
                ]);

                if (!$user || !Hash::check($this->password, $user->password)) {
                    RateLimiter::hit($this->throttleKey());
                    throw ValidationException::withMessages([
                        'email' => 'Invalid credentials.',
                    ]);
                }

                // Create a new user instance for Auth
                $userModel = new \App\Models\User();
                $userModel->setConnection('tenant');
                $userModel->forceFill([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => $user->password,
                    'is_admin' => $user->is_admin
                ]);
                
                // Login the user
                Auth::login($userModel);
            }

            Log::info('Authentication successful', [
                'email' => $this->email,
                'is_central_domain' => $isCentralDomain,
                'is_logged_in' => Auth::check(),
                'current_connection' => DB::getDefaultConnection()
            ]);
            
            RateLimiter::clear($this->throttleKey());

        } catch (ValidationException $e) {
            Log::error('Validation error during authentication', [
                'error' => $e->getMessage(),
                'host' => $host,
                'email' => $this->email
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'host' => $host,
                'email' => $this->email,
                'current_connection' => DB::getDefaultConnection()
            ]);
            throw ValidationException::withMessages([
                'email' => 'Authentication error occurred: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
