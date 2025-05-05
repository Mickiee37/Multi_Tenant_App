<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Http\Controllers\TenantApplicationController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // Authentication Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Root route - redirect based on auth status
    Route::get('/', function () {
        return auth()->check() 
            ? redirect('/admin/tenant-dashboard')
            : redirect()->route('login');
    });

    // Protected Routes
    Route::middleware(['auth'])->group(function () {
        // Catch any attempts to access central dashboard
        Route::get('/admin/dashboard', function() {
            return redirect('/admin/tenant-dashboard');
        });

        // Dashboard routes
        Route::get('/admin/tenant-dashboard', [TenantApplicationController::class, 'adminDashboard'])
            ->name('tenant.admin.dashboard');

        // Product Management Routes
        Route::prefix('admin')->group(function () {
            Route::get('/products', [TenantApplicationController::class, 'products'])
                ->name('tenant.products.index');
            Route::post('/products', [TenantApplicationController::class, 'storeProduct'])
                ->name('tenant.products.store');
            Route::get('/products/{id}/edit', [TenantApplicationController::class, 'editProduct'])
                ->name('tenant.products.edit');
            Route::put('/products/{id}', [TenantApplicationController::class, 'updateProduct'])
                ->name('tenant.products.update');
            Route::delete('/products/{id}', [TenantApplicationController::class, 'deleteProduct'])
                ->name('tenant.products.delete');
        });
    });
});
