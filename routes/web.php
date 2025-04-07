<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantRegisterController;
use App\Http\Controllers\TenantApplicationController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\GoogleAuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->is_admin) {
        return redirect()->route('admin.tenant.applications');
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
Route::get('/register-tenant', [TenantApplicationController::class, 'showRegistrationForm'])
    ->name('tenant.register');
Route::post('/register-tenant', [TenantApplicationController::class, 'register']);
Route::get('/register-tenant/success', function () {
    return view('tenant.register-success');
})->name('tenant.register.success');

// Admin Routes with full middleware class name
Route::middleware(['auth', AdminMiddleware::class])->group(function () {
    Route::get('/admin/tenant-applications', [TenantApplicationController::class, 'adminDashboard'])
        ->name('admin.tenant.applications');
    Route::post('/admin/tenant-applications/{application}/approve', [TenantApplicationController::class, 'approve'])
        ->name('admin.tenant.approve');
    Route::post('/admin/tenant-applications/{application}/reject', [TenantApplicationController::class, 'reject'])
        ->name('admin.tenant.reject');
});

// Google OAuth Routes
Route::get('/oauth/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/oauth/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

require __DIR__.'/auth.php';
