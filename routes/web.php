<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class.'@index')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
});
