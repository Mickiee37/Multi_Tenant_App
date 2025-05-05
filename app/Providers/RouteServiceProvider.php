<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/admin/tenant-dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes()
    {
        // Central domain routes
        foreach ($this->centralDomains() as $domain) {
            Route::middleware(['web', 'central.domain'])
                ->domain($domain)
                ->group(base_path('routes/web.php'));
        }

        // Tenant routes with proper middleware
        Route::middleware([
            'web',
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
        ])->group(base_path('routes/tenant.php'));
    }

    protected function mapApiRoutes()
    {
        foreach ($this->centralDomains() as $domain) {
            Route::prefix('api')
                ->domain($domain)
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        }
    }

    protected function centralDomains(): array
    {
        return config('tenancy.central_domains', [
            'localhost',
            'localhost:8000',
            '127.0.0.1',
            '127.0.0.1:8000'
        ]);
    }
} 