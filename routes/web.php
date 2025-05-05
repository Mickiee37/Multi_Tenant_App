<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantApplicationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;

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

Route::middleware(['web'])->group(function () {
    // Redirect to tenant domain if accessed through tenant domain
    if (tenant()) {
        return redirect()->route('tenant.admin.dashboard');
    }

    // Central domain routes
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
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

// Authentication Routes
    Route::middleware('guest')->group(function () {
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
    });
    
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout')
        ->middleware('auth');
});

require __DIR__.'/auth.php';
