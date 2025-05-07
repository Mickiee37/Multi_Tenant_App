<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantApplicationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\CentralDomainMiddleware;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\TenantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Central domain routes - protected by CentralDomainMiddleware
Route::middleware(['web', CentralDomainMiddleware::class])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/dashboard', function () {
        if (tenant()) {
            return redirect('/admin/tenant-dashboard');
        }
        if (auth()->check() && auth()->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    // Profile routes
    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Admin Routes (only accessible from central domain)
    Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
            Route::get('/tenant-applications', [AdminController::class, 'tenantApplications'])->name('admin.tenant-applications');
            Route::post('/tenant-applications/{id}/approve', [AdminController::class, 'approveTenantApplication'])->name('admin.tenant-applications.approve');
            Route::post('/tenant-applications/{id}/reject', [AdminController::class, 'rejectTenantApplication'])->name('admin.tenant-applications.reject');
            Route::post('/tenant/{id}/deactivate', [AdminController::class, 'deactivateTenant'])->name('admin.tenant.deactivate');
        });

    // Tenant registration routes
    Route::middleware('auth')->group(function () {
        Route::get('/tenant/register', [TenantApplicationController::class, 'showRegistrationForm'])->name('tenant.register');
        Route::post('/tenant/register', [TenantApplicationController::class, 'register'])->name('tenant.register.submit');
    });

    Route::get('/sign-up', [TenantApplicationController::class, 'showRegistrationForm'])
        ->name('tenant.signup')
        ->middleware('guest');

    Route::post('/sign-up', [TenantApplicationController::class, 'register'])
        ->name('tenant.register');

    Route::get('/sign-up/success', function () {
        return view('tenant.register-success');
    })->name('tenant.register.success');

    // Google OAuth Routes
    Route::get('/oauth/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/oauth/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
});

// Authentication Routes - these should be accessible from both central and tenant domains
Route::middleware('web')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Add universal diagnostic route that works on all domains
Route::get('/diagnostic/tenant-check', [DiagnosticController::class, 'tenantCheck']);

// Manual host-based tenant routes for tway tenant
Route::domain('tway.localhost')->group(function () {
    // Direct routes for specific tenant without requiring tenant resolution
    Route::get('/', function() {
        return redirect('/login');
    });
    
    Route::get('/login', function() {
        return app()->call([new App\Http\Controllers\Auth\LoginController(), 'showLoginForm']);
    })->name('tway.login');
    
    Route::post('/login', function(\Illuminate\Http\Request $request) {
        return app()->call([new App\Http\Controllers\Auth\LoginController(), 'login'], ['request' => $request]);
    });
    
    Route::middleware(['auth'])->group(function() {
        Route::get('/dashboard', function() {
            return redirect('/admin/tenant-dashboard');
        });
        
        Route::get('/admin/tenant-dashboard', function() {
            // Get the tenant for this domain
            $tenant = \App\Models\Tenant::where('domain', 'tway.localhost')->first();
            
            if (!$tenant) {
                return redirect('/login')->with('error', 'Tenant not found');
            }
            
            // Configure tenant database connection
            config(['database.connections.tenant.database' => $tenant->database]);
            \Illuminate\Support\Facades\DB::purge('tenant');
            \Illuminate\Support\Facades\DB::reconnect('tenant');
            
            // Get products from tenant database
            try {
                $products = \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('products')
                    ->select('id', 'name', 'price', 'description', 'image', 'created_at', 'updated_at')
                    ->get();
                
                \Illuminate\Support\Facades\Log::info('Fetched products for tway tenant', [
                    'count' => $products->count()
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error fetching products for tway tenant', [
                    'error' => $e->getMessage()
                ]);
                $products = collect();
            }
            
            return view('tenant.dashboard', [
                'tenant' => $tenant,
                'products' => $products
            ]);
        })->name('tway.dashboard');
    });
});

// Manual host-based tenant routes for hggh tenant
Route::domain('hggh.localhost')->group(function () {
    // Direct routes for specific tenant without requiring tenant resolution
    Route::get('/', function() {
        return redirect('/login');
    });
    
    Route::get('/login', function() {
        return app()->call([new App\Http\Controllers\Auth\LoginController(), 'showLoginForm']);
    })->name('hggh.login');
    
    Route::post('/login', function(\Illuminate\Http\Request $request) {
        return app()->call([new App\Http\Controllers\Auth\LoginController(), 'login'], ['request' => $request]);
    });
    
    Route::middleware(['auth'])->group(function() {
        Route::get('/dashboard', function() {
            return redirect('/admin/tenant-dashboard');
        });
        
        Route::get('/admin/tenant-dashboard', function() {
            // Get the tenant for this domain
            $tenant = \App\Models\Tenant::where('domain', 'hggh.localhost')->first();
            
            if (!$tenant) {
                return redirect('/login')->with('error', 'Tenant not found');
            }
            
            // Configure tenant database connection
            config(['database.connections.tenant.database' => $tenant->database]);
            \Illuminate\Support\Facades\DB::purge('tenant');
            \Illuminate\Support\Facades\DB::reconnect('tenant');
            
            // Get products from tenant database
            try {
                $products = \Illuminate\Support\Facades\DB::connection('tenant')
                    ->table('products')
                    ->select('id', 'name', 'price', 'description', 'image', 'created_at', 'updated_at')
                    ->get();
                
                \Illuminate\Support\Facades\Log::info('Fetched products for hggh tenant', [
                    'count' => $products->count()
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error fetching products for hggh tenant', [
                    'error' => $e->getMessage()
                ]);
                $products = collect();
            }
            
            return view('tenant.dashboard', [
                'tenant' => $tenant,
                'products' => $products
            ]);
        })->name('hggh.dashboard');
    });
});

require __DIR__.'/auth.php';
