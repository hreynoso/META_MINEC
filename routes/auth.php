<?php

use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LocalAdminController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// SSO Google Workspace (sin 2FA propio — control A.9.4.2 vía SSO institucional)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

    // Acceso temporal de demo (gated por DEMO_LOGIN_ENABLED)
    Route::post('/demo-login', [DemoLoginController::class, 'store'])->name('demo.login');

    // Acceso local exclusivo de la cuenta Super Admin (break-glass)
    Route::post('/acceso-administrativo', [LocalAdminController::class, 'store'])->name('local-admin.login');
});

Route::post('/logout', [GoogleController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
