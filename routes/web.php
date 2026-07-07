<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class.'@index')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Portafolio de proyectos de inversión pública
    Route::get('/proyectos', [ProjectController::class, 'index'])->name('proyectos.index');

    // Configuración → identidad visual (logo sidebar, logo/fondo de login, favicon)
    Route::get('/configuracion', [BrandingController::class, 'edit'])->name('configuracion.edit');
    Route::post('/configuracion/branding', [BrandingController::class, 'update'])->name('configuracion.branding.update');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
});
