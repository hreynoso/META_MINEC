<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class.'@index')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Portafolio de proyectos de inversión pública
    Route::get('/proyectos', [ProjectController::class, 'index'])->name('proyectos.index');
    Route::post('/proyectos', [ProjectController::class, 'store'])->name('proyectos.store');
    Route::put('/proyectos/{project}', [ProjectController::class, 'update'])->name('proyectos.update');
    Route::delete('/proyectos/{project}', [ProjectController::class, 'destroy'])->name('proyectos.destroy');

    // Indicadores de gestión (KPIs)
    Route::get('/kpis', [KpiController::class, 'index'])->name('kpis.index');
    Route::post('/kpis', [KpiController::class, 'store'])->name('kpis.store');
    Route::put('/kpis/{kpi}', [KpiController::class, 'update'])->name('kpis.update');
    Route::delete('/kpis/{kpi}', [KpiController::class, 'destroy'])->name('kpis.destroy');

    // Configuración → identidad visual (logo sidebar, logo/fondo de login, favicon)
    Route::get('/configuracion', [BrandingController::class, 'edit'])->name('configuracion.edit');
    Route::post('/configuracion/branding', [BrandingController::class, 'update'])->name('configuracion.branding.update');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
});
