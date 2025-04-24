<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantRegisterController;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $host = request()->getHost();
    
    // If we're on a tenant domain
    if (strpos($host, '.localhost') !== false) {
        if (auth()->user()->is_admin) {
            return redirect()->route('tenant.admin.dashboard');
        }
        return view('tenant.dashboard');
    }
    
    // If we're on the central domain
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//tenant registration
Route::middleware('auth')->group(function () {
    Route::get('/tenant/register', [TenantRegisterController::class, 'showRegistrationForm'])->name('tenant.register');
    Route::post('/tenant/register', [TenantRegisterController::class, 'register'])->name('tenant.register.post');
});

// Tenant Registration Routes
Route::get('/sign-up', [TenantApplicationController::class, 'showRegistrationForm'])
    ->name('tenant.signup')
    ->middleware('guest');

Route::post('/sign-up', [TenantApplicationController::class, 'register'])
    ->name('tenant.register');

Route::get('/sign-up/success', function () {
    return view('tenant.register-success');
})->name('tenant.register.success');

// Admin Routes
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/admin/tenant-applications', [TenantApplicationController::class, 'adminDashboard'])
        ->name('admin.tenant-applications');
    Route::post('/admin/tenant-applications/{application}/approve', [TenantApplicationController::class, 'approve'])
        ->name('admin.tenant-applications.approve');
    Route::post('/admin/tenant-applications/{application}/reject', [TenantApplicationController::class, 'reject'])
        ->name('admin.tenant-applications.reject');
});

// Google OAuth Routes
Route::get('/oauth/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/oauth/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::middleware(['auth'])->group(function () {
    Route::get('/tenant/backup', [TenantApplicationController::class, 'requestBackup'])
        ->name('tenant.backup');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Central Admin Routes (only accessible from central domain)
Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class, \App\Http\Middleware\CentralDomainMiddleware::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/tenant-applications', [AdminController::class, 'tenantApplications'])->name('admin.tenant-applications');
        Route::post('/tenant-applications/{id}/approve', [AdminController::class, 'approveTenantApplication'])->name('admin.tenant-applications.approve');
        Route::post('/tenant-applications/{id}/reject', [AdminController::class, 'rejectTenantApplication'])->name('admin.tenant-applications.reject');
    });

// Tenant Routes (only accessible from tenant domains)
Route::middleware(['auth', \App\Http\Middleware\TenantMiddleware::class])
    ->prefix('admin')
    ->group(function () {
        Route::get('/tenant-dashboard', [TenantApplicationController::class, 'adminDashboard'])->name('tenant.admin.dashboard');
        Route::get('/backup', [TenantApplicationController::class, 'requestBackup'])->name('tenant.backup');
    });

// Tenant Application Routes
Route::get('/tenant-application', [TenantApplicationController::class, 'showApplicationForm'])->name('tenant.application');
Route::post('/tenant-application', [TenantApplicationController::class, 'submitApplication'])->name('tenant.application.submit');

require __DIR__.'/auth.php';
