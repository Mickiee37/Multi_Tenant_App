<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantRegisterController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
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
require __DIR__.'/auth.php';
