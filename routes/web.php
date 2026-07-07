<?php

use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\MinisterController;
use App\Http\Controllers\PredictiveController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class.'@index')->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Despacho de la Ministra (tablero ejecutivo + informe presidencial con IA)
    Route::get('/ministra', [MinisterController::class, 'index'])->name('ministra.index');
    Route::post('/ministra/informe', [MinisterController::class, 'generateReport'])->name('ministra.report');

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

    // IA Predictiva (modelo META-PREDICT + recomendación vía API de IA configurado)
    Route::get('/ia-predictiva', [PredictiveController::class, 'index'])->name('ia-predictiva.index');
    Route::get('/ia-predictiva/{project}/recomendacion', [PredictiveController::class, 'recommendation'])->name('ia-predictiva.recommendation');

    // Reportes institucionales (PDF vía DomPDF, XLSX vía OpenSpout)
    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/{report}/vista-previa', [ReportController::class, 'preview'])->name('reportes.preview');
    Route::get('/reportes/{report}/descargar', [ReportController::class, 'download'])->name('reportes.download');

    // Configuración → identidad visual (logo sidebar, logo/fondo de login, favicon)
    Route::get('/configuracion', [BrandingController::class, 'edit'])->name('configuracion.edit');
    Route::post('/configuracion/branding', [BrandingController::class, 'update'])->name('configuracion.branding.update');

    // Configuración → Inteligencia Artificial (proveedor, clave del API, modelo)
    Route::get('/configuracion/ia', [AiSettingsController::class, 'edit'])->name('configuracion.ia.edit');
    Route::post('/configuracion/ia', [AiSettingsController::class, 'update'])->name('configuracion.ia.update');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
});
