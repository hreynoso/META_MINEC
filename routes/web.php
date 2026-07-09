<?php

use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\GoogleSsoSettingsController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MailWebhookController;
use App\Http\Controllers\MemoirController;
use App\Http\Controllers\MinisterController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\PredictiveController;
use App\Http\Controllers\ProfileController;
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
    Route::post('/ministra/informe/pdf', [MinisterController::class, 'reportPdf'])->name('ministra.report.pdf');

    // Portafolio de proyectos de inversión pública
    Route::get('/proyectos', [ProjectController::class, 'index'])->name('proyectos.index');
    Route::post('/proyectos', [ProjectController::class, 'store'])->name('proyectos.store');
    Route::put('/proyectos/{project}', [ProjectController::class, 'update'])->name('proyectos.update');
    Route::delete('/proyectos/{project}', [ProjectController::class, 'destroy'])->name('proyectos.destroy');

    // Indicadores de gestión (KPIs)
    Route::get('/kpis/export', [KpiController::class, 'export'])->name('kpis.export');
    Route::get('/kpis', [KpiController::class, 'index'])->name('kpis.index');
    Route::post('/kpis', [KpiController::class, 'store'])->name('kpis.store');
    Route::put('/kpis/{kpi}', [KpiController::class, 'update'])->name('kpis.update');
    Route::delete('/kpis/{kpi}', [KpiController::class, 'destroy'])->name('kpis.destroy');

    // Red de Gestores (chat institucional por canales)
    Route::get('/red-de-gestores', [NetworkController::class, 'index'])->name('red-gestores.index');
    Route::get('/red-de-gestores/mensajes', [NetworkController::class, 'messages'])->name('red-gestores.messages');
    Route::post('/red-de-gestores/mensajes', [NetworkController::class, 'store'])->name('red-gestores.store');
    Route::post('/red-de-gestores/notificar-riesgos', [NetworkController::class, 'notifyRisks'])->name('red-gestores.notify');

    // Memorias institucionales (borrador generado con el API de IA configurado)
    Route::get('/memorias', [MemoirController::class, 'index'])->name('memorias.index');
    Route::post('/memorias/generar', [MemoirController::class, 'generate'])->name('memorias.generate');

    // IA Predictiva (modelo META-PREDICT + recomendación vía API de IA configurado)
    Route::get('/ia-predictiva', [PredictiveController::class, 'index'])->name('ia-predictiva.index');
    Route::get('/ia-predictiva/{project}/recomendacion', [PredictiveController::class, 'recommendation'])->name('ia-predictiva.recommendation');
    Route::get('/ia-predictiva/{project}/historial', [PredictiveController::class, 'history'])->name('ia-predictiva.history');
    Route::get('/ia-predictiva/{project}/informe', [PredictiveController::class, 'report'])->name('ia-predictiva.report');

    // Reportes institucionales (PDF vía DomPDF, XLSX vía OpenSpout)
    Route::get('/reportes', [ReportController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/ejecucion-instituciones/export', [ReportController::class, 'institutionExport'])->name('reportes.institution-export');
    Route::get('/reportes/{report}/vista-previa', [ReportController::class, 'preview'])->name('reportes.preview');
    Route::get('/reportes/{report}/descargar', [ReportController::class, 'download'])->name('reportes.download');

    // ── Área de administración: acceso privilegiado (ISO 27001 A.5.15 / A.8.2) ──
    // Configuración general, correo, notificaciones y logs: Super Admin o Administrador.
    Route::middleware('role:Super Admin|Administrador')->group(function () {
        // Logs del Sistema (bitácora de actividad)
        Route::get('/logs/export', [LogController::class, 'export'])->name('logs.export');
        Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

        // Configuración → Branding (logos, imágenes y colores del sistema)
        Route::get('/configuracion', [BrandingController::class, 'edit'])->name('configuracion.edit');
        Route::post('/configuracion/branding', [BrandingController::class, 'update'])->name('configuracion.branding.update');
        Route::post('/configuracion/branding/colores', [BrandingController::class, 'updateColors'])->name('configuracion.branding.colors');

        // Configuración → Correo (proveedor Mailgun/SMTP + envío de prueba)
        Route::get('/configuracion/correo', [MailSettingsController::class, 'edit'])->name('configuracion.correo.edit');
        Route::post('/configuracion/correo', [MailSettingsController::class, 'update'])->name('configuracion.correo.update');
        Route::post('/configuracion/correo/prueba', [MailSettingsController::class, 'test'])->name('configuracion.correo.test');

        // Configuración → Notificaciones
        Route::get('/configuracion/notificaciones', [NotificationSettingsController::class, 'edit'])->name('configuracion.notificaciones.edit');
        Route::post('/configuracion/notificaciones', [NotificationSettingsController::class, 'update'])->name('configuracion.notificaciones.update');
    });

    // ── Máximo privilegio: solo Super Admin (cuenta local break-glass) ──
    Route::middleware('role:Super Admin')->group(function () {
        // Configuración → Usuarios
        Route::get('/configuracion/usuarios/export', [UserController::class, 'export'])->name('configuracion.usuarios.export');
        Route::get('/configuracion/usuarios', [UserController::class, 'index'])->name('configuracion.usuarios.index');
        Route::post('/configuracion/usuarios', [UserController::class, 'store'])->name('configuracion.usuarios.store');
        Route::put('/configuracion/usuarios/{user}', [UserController::class, 'update'])->name('configuracion.usuarios.update');
        Route::delete('/configuracion/usuarios/{user}', [UserController::class, 'destroy'])->name('configuracion.usuarios.destroy');

        // Configuración → Roles y permisos
        Route::get('/configuracion/roles', [RoleController::class, 'index'])->name('configuracion.roles.index');
        Route::post('/configuracion/roles', [RoleController::class, 'store'])->name('configuracion.roles.store');
        Route::put('/configuracion/roles/{role}', [RoleController::class, 'update'])->name('configuracion.roles.update');
        Route::delete('/configuracion/roles/{role}', [RoleController::class, 'destroy'])->name('configuracion.roles.destroy');

        // Configuración → Inteligencia Artificial (proveedor, clave del API, modelo)
        Route::get('/configuracion/ia', [AiSettingsController::class, 'edit'])->name('configuracion.ia.edit');
        Route::post('/configuracion/ia', [AiSettingsController::class, 'update'])->name('configuracion.ia.update');

        // Configuración → SSO Google Workspace (credenciales OAuth)
        Route::get('/configuracion/sso', [GoogleSsoSettingsController::class, 'edit'])->name('configuracion.sso.edit');
        Route::post('/configuracion/sso', [GoogleSsoSettingsController::class, 'update'])->name('configuracion.sso.update');
    });

    // Perfil del usuario (cambio de foto de perfil)
    Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('perfil.foto.update');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');
});

// Webhook público de Mailgun (sin auth ni CSRF; excluido en bootstrap/app.php).
Route::post('/webhooks/mailgun', [MailWebhookController::class, 'mailgun'])->name('webhooks.mailgun');
