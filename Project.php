<?php

use App\Http\Controllers\Auth\AzureController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// SSO Office 365 / Azure AD (sin 2FA — control A.9.4.2 via SSO institucional)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::get('/auth/azure/redirect', [AzureController::class, 'redirect'])->name('azure.redirect');
    Route::get('/auth/azure/callback', [AzureController::class, 'callback'])->name('azure.callback');

    // Acceso temporal de demo (gated por DEMO_LOGIN_ENABLED)
    Route::post('/demo-login', [DemoLoginController::class, 'store'])->name('demo.login');
});

Route::post('/logout', [AzureController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
