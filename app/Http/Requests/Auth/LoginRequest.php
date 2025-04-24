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

        // Get the current host and extract domain
        $host = request()->getHost();
        $domain = str_replace('.localhost:8000', '', $host);
        $domain = str_replace('.localhost', '', $domain);

        Log::info('Authentication attempt', [
            'host' => $host,
            'domain' => $domain,
            'email' => $this->email
        ]);

        try {
            // Find tenant by domain
            DB::setDefaultConnection('mysql');
            $tenant = DB::table('tenants')->where('domain', $domain)->first();
            
            Log::info('Tenant lookup result', [
                'tenant_found' => !is_null($tenant),
                'domain' => $domain,
                'database' => $tenant ? $tenant->database : null
            ]);

            if (!$tenant) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'Invalid tenant domain.',
                ]);
            }

            // Store tenant database in session first
            session(['tenant_database' => $tenant->database]);

            // Configure and switch to tenant database
            Config::set('database.connections.tenant.database', $tenant->database);
            DB::purge('tenant');
            DB::reconnect('tenant');
            DB::setDefaultConnection('tenant');

            // Get user from tenant database
            $user = DB::connection('tenant')->table('users')
                ->where('email', $this->email)
                ->first();

            Log::info('User lookup result', [
                'user_found' => !is_null($user),
                'email' => $this->email,
                'database' => $tenant->database
            ]);

            if (!$user) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'email' => 'User not found.',
                ]);
            }

            // Verify password
            if (!Hash::check($this->password, $user->password)) {
                RateLimiter::hit($this->throttleKey());
                Log::warning('Password verification failed', [
                    'email' => $this->email,
                    'database' => $tenant->database,
                    'provided_password' => $this->password,
                    'stored_hash' => $user->password
                ]);
                throw ValidationException::withMessages([
                    'email' => 'Invalid credentials.',
                ]);
            }

            // Create a new user instance for Auth
            $userModel = new \App\Models\User();
            $userModel->setConnection('tenant'); // Explicitly set connection
            $userModel->forceFill([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'password' => $user->password,
                'is_admin' => $user->is_admin
            ]);
            
            // Login the user
            Auth::login($userModel);

            Log::info('Authentication successful', [
                'email' => $this->email,
                'database' => $tenant->database,
                'user_id' => $user->id,
                'is_logged_in' => Auth::check(),
                'current_connection' => DB::getDefaultConnection(),
                'session_database' => session('tenant_database')
            ]);
            
            RateLimiter::clear($this->throttleKey());

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'domain' => $domain,
                'email' => $this->email,
                'current_connection' => DB::getDefaultConnection(),
                'session_database' => session('tenant_database')
            ]);
            throw ValidationException::withMessages([
                'email' => 'Authentication error occurred. Please check your credentials and try again.',
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
