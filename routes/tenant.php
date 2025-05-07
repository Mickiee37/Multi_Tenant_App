<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DiagnosticController;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here is where you can register tenant-specific routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group and the tenant middleware.
|
*/

// Diagnostic route that works without auth
Route::get('/diagnostic/tenant-check', [DiagnosticController::class, 'tenantCheck']);

// Root route - always redirect to login
Route::get('/', function() {
    return redirect('/login');
});

// Guest routes - available without authentication
Route::middleware(['guest'])->group(function() {
    // Auth routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// Protected routes - require authentication
Route::middleware(['auth'])->group(function () {
    // Logout route
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Redirect old dashboard to tenant dashboard
    Route::get('/dashboard', function() {
        return redirect('/admin/tenant-dashboard');
    })->name('dashboard');

    // Admin routes
    Route::prefix('admin')->group(function () {
        // Tenant Dashboard
        Route::get('/tenant-dashboard', [TenantController::class, 'dashboard'])
            ->name('tenant.dashboard');
        
        // Product Management Routes
        Route::prefix('products')->name('tenant.products.')->group(function () {
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::put('/{product}', [ProductController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductController::class, 'destroy'])->name('delete');
            Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        });
    });
});
