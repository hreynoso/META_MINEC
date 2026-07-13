<?php

use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\BackupSettingsController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\GoogleSsoSettingsController;
use App\Http\Controllers\Admin\LanguageSettingsController;
use App\Http\Controllers\Admin\NotificationSettingsController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AupController;
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

    // ── Módulos operativos: RBAC por permiso (ISO 27001 A.8.3) ──
    // ver = consulta/descarga; gestionar/generar/etc. = acciones de escritura.

    // Despacho de la Ministra (tablero ejecutivo + informe presidencial con IA)
    Route::get('/ministra', [MinisterController::class, 'index'])->middleware('permission:ministra.ver')->name('ministra.index');
    Route::post('/ministra/informe', [MinisterController::class, 'generateReport'])->middleware('permission:ministra.informe')->name('ministra.report');
    Route::post('/ministra/informe/pdf', [MinisterController::class, 'reportPdf'])->middleware('permission:ministra.informe')->name('ministra.report.pdf');
    Route::post('/ministra/informe/docx', [MinisterController::class, 'reportDocx'])->middleware('permission:ministra.informe')->name('ministra.report.docx');
    Route::get('/ministra/historial', [MinisterController::class, 'history'])->middleware('permission:ministra.ver')->name('ministra.history');
    Route::get('/ministra/informe/{report}/pdf', [MinisterController::class, 'reportStored'])->middleware('permission:ministra.ver')->name('ministra.report.stored');
    Route::get('/ministra/informe/{report}/docx', [MinisterController::class, 'reportStoredDocx'])->middleware('permission:ministra.ver')->name('ministra.report.stored.docx');

    // Portafolio de proyectos de inversión pública
    Route::get('/proyectos', [ProjectController::class, 'index'])->middleware('permission:proyectos.ver')->name('proyectos.index');
    Route::post('/proyectos', [ProjectController::class, 'store'])->middleware('permission:proyectos.gestionar')->name('proyectos.store');
    Route::put('/proyectos/{project}', [ProjectController::class, 'update'])->middleware('permission:proyectos.gestionar')->name('proyectos.update');
    Route::delete('/proyectos/{project}', [ProjectController::class, 'destroy'])->middleware('permission:proyectos.gestionar')->name('proyectos.destroy');

    // Indicadores de gestión (KPIs)
    Route::get('/kpis/export', [KpiController::class, 'export'])->middleware('permission:kpis.ver')->name('kpis.export');
    Route::get('/kpis', [KpiController::class, 'index'])->middleware('permission:kpis.ver')->name('kpis.index');
    Route::post('/kpis', [KpiController::class, 'store'])->middleware('permission:kpis.gestionar')->name('kpis.store');
    Route::put('/kpis/{kpi}', [KpiController::class, 'update'])->middleware('permission:kpis.gestionar')->name('kpis.update');
    Route::delete('/kpis/{kpi}', [KpiController::class, 'destroy'])->middleware('permission:kpis.gestionar')->name('kpis.destroy');

    // Red de Gestores (chat institucional por canales)
    Route::get('/red-de-gestores', [NetworkController::class, 'index'])->middleware('permission:gestores.participar')->name('red-gestores.index');
    Route::get('/red-de-gestores/mensajes', [NetworkController::class, 'messages'])->middleware('permission:gestores.participar')->name('red-gestores.messages');
    Route::post('/red-de-gestores/mensajes', [NetworkController::class, 'store'])->middleware('permission:gestores.participar')->name('red-gestores.store');
    Route::post('/red-de-gestores/notificar-riesgos', [NetworkController::class, 'notifyRisks'])->middleware('permission:gestores.notificar')->name('red-gestores.notify');

    // Memorias institucionales (borrador generado con el API de IA configurado)
    Route::middleware('permission:memorias.generar')->group(function () {
        Route::get('/memorias', [MemoirController::class, 'index'])->name('memorias.index');
        Route::post('/memorias/generar', [MemoirController::class, 'generate'])->name('memorias.generate');
        Route::get('/memorias/historial', [MemoirController::class, 'history'])->name('memorias.history');
        Route::get('/memorias/{memoir}/informe', [MemoirController::class, 'report'])->name('memorias.report');
        Route::get('/memorias/{memoir}/docx', [MemoirController::class, 'reportDocx'])->name('memorias.report.docx');
    });

    // IA Predictiva (modelo META-PREDICT + recomendación vía API de IA configurado)
    Route::get('/ia-predictiva', [PredictiveController::class, 'index'])->middleware('permission:ia.ver')->name('ia-predictiva.index');
    Route::get('/ia-predictiva/{project}/recomendacion', [PredictiveController::class, 'recommendation'])->middleware('permission:ia.recomendar')->name('ia-predictiva.recommendation');
    Route::get('/ia-predictiva/{project}/historial', [PredictiveController::class, 'history'])->middleware('permission:ia.ver')->name('ia-predictiva.history');
    Route::get('/ia-predictiva/{project}/informe', [PredictiveController::class, 'report'])->middleware('permission:ia.ver')->name('ia-predictiva.report');

    // Reportes institucionales (PDF vía DomPDF, XLSX vía OpenSpout)
    Route::get('/reportes', [ReportController::class, 'index'])->middleware('permission:reportes.ver')->name('reportes.index');
    Route::get('/reportes/ejecucion-instituciones/export', [ReportController::class, 'institutionExport'])->middleware('permission:reportes.generar')->name('reportes.institution-export');
    Route::get('/reportes/{report}/vista-previa', [ReportController::class, 'preview'])->middleware('permission:reportes.ver')->name('reportes.preview');
    Route::get('/reportes/{report}/descargar', [ReportController::class, 'download'])->middleware('permission:reportes.generar')->name('reportes.download');

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
        Route::post('/configuracion/notificaciones/enviar', [NotificationSettingsController::class, 'sendNow'])->name('configuracion.notificaciones.enviar');

        // Configuración → Instituciones (mantenimiento; alimenta el desplegable del sistema)
        Route::get('/configuracion/instituciones/export', [InstitutionController::class, 'export'])->name('configuracion.instituciones.export');
        Route::get('/configuracion/instituciones', [InstitutionController::class, 'index'])->name('configuracion.instituciones.index');
        Route::post('/configuracion/instituciones', [InstitutionController::class, 'store'])->name('configuracion.instituciones.store');
        Route::put('/configuracion/instituciones/{institution}', [InstitutionController::class, 'update'])->name('configuracion.instituciones.update');
        Route::delete('/configuracion/instituciones/{institution}', [InstitutionController::class, 'destroy'])->name('configuracion.instituciones.destroy');

        // Configuración → Idioma (español / inglés)
        Route::get('/configuracion/idioma', [LanguageSettingsController::class, 'edit'])->name('configuracion.idioma.edit');
        Route::post('/configuracion/idioma', [LanguageSettingsController::class, 'update'])->name('configuracion.idioma.update');

        // Configuración → Seguridad
        Route::get('/configuracion/seguridad', [SecurityController::class, 'index'])->name('configuracion.seguridad');
        // A.8.8 Análisis de dependencias (programable, con informe PDF por correo)
        Route::get('/configuracion/seguridad/dependencias', [SecurityController::class, 'dependencies'])->name('configuracion.seguridad.dependencias');
        Route::post('/configuracion/seguridad/dependencias/ejecutar', [SecurityController::class, 'runDependencies'])->name('configuracion.seguridad.dependencias.ejecutar');
        Route::post('/configuracion/seguridad/dependencias/programacion', [SecurityController::class, 'updateDependencySchedule'])->name('configuracion.seguridad.dependencias.programacion');
        Route::get('/configuracion/seguridad/dependencias/pdf', [SecurityController::class, 'downloadDependencyReport'])->name('configuracion.seguridad.dependencias.pdf');
        Route::post('/configuracion/seguridad/dependencias/enviar', [SecurityController::class, 'sendDependencyReport'])->name('configuracion.seguridad.dependencias.enviar');
        // A.8.16 Alertas de seguridad (correo al personal de seguridad TIC)
        Route::get('/configuracion/seguridad/alertas', [SecurityController::class, 'alerts'])->name('configuracion.seguridad.alertas');
        Route::post('/configuracion/seguridad/alertas', [SecurityController::class, 'updateAlerts'])->name('configuracion.seguridad.alertas.update');
    });

    // ── Máximo privilegio: solo Super Admin (cuenta local break-glass) ──
    Route::middleware('role:Super Admin')->group(function () {
        // Configuración → Usuarios (incluye la revisión de accesos, A.5.18)
        Route::get('/configuracion/usuarios/export', [UserController::class, 'export'])->name('configuracion.usuarios.export');
        Route::get('/configuracion/usuarios', [UserController::class, 'index'])->name('configuracion.usuarios.index');
        Route::post('/configuracion/usuarios', [UserController::class, 'store'])->name('configuracion.usuarios.store');
        Route::put('/configuracion/usuarios/{user}', [UserController::class, 'update'])->name('configuracion.usuarios.update');
        Route::delete('/configuracion/usuarios/{user}', [UserController::class, 'destroy'])->name('configuracion.usuarios.destroy');
        // Acciones de acceso: bloquear/desbloquear y registrar la revisión de accesos.
        Route::post('/configuracion/usuarios/{user}/bloqueo', [UserController::class, 'toggleBlock'])->name('configuracion.usuarios.bloqueo');
        Route::post('/configuracion/usuarios/revision', [UserController::class, 'recordReview'])->name('configuracion.usuarios.revision');

        // Configuración → Roles y permisos
        Route::get('/configuracion/roles', [RoleController::class, 'index'])->name('configuracion.roles.index');
        Route::post('/configuracion/roles', [RoleController::class, 'store'])->name('configuracion.roles.store');
        Route::put('/configuracion/roles/{role}', [RoleController::class, 'update'])->name('configuracion.roles.update');
        Route::delete('/configuracion/roles/{role}', [RoleController::class, 'destroy'])->name('configuracion.roles.destroy');

        // Configuración → Inteligencia Artificial (proveedor, clave del API, modelo)
        Route::get('/configuracion/ia', [AiSettingsController::class, 'edit'])->name('configuracion.ia.edit');
        Route::post('/configuracion/ia', [AiSettingsController::class, 'update'])->name('configuracion.ia.update');
        Route::post('/configuracion/ia/prueba', [AiSettingsController::class, 'test'])->name('configuracion.ia.test');

        // Configuración → SSO Google Workspace (credenciales OAuth)
        Route::get('/configuracion/sso', [GoogleSsoSettingsController::class, 'edit'])->name('configuracion.sso.edit');
        Route::post('/configuracion/sso', [GoogleSsoSettingsController::class, 'update'])->name('configuracion.sso.update');

        // Configuración → Respaldos automáticos (A.8.13; Dropbox / Google Cloud)
        Route::get('/configuracion/respaldos', [BackupSettingsController::class, 'edit'])->name('configuracion.respaldos.edit');
        Route::post('/configuracion/respaldos', [BackupSettingsController::class, 'update'])->name('configuracion.respaldos.update');
        Route::post('/configuracion/respaldos/prueba', [BackupSettingsController::class, 'test'])->name('configuracion.respaldos.test');
        Route::post('/configuracion/respaldos/ejecutar', [BackupSettingsController::class, 'runNow'])->name('configuracion.respaldos.ejecutar');
    });

    // Perfil del usuario (cambio de foto de perfil)
    Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('perfil.foto.update');
    Route::delete('/perfil/foto', [ProfileController::class, 'deletePhoto'])->name('perfil.foto.delete');

    // Keep-alive de sesion (cada 15 min desde el cliente)
    Route::post('/keep-alive', [SessionController::class, 'keepAlive'])->name('keep-alive');

    // Lista de usuarios conectados (modal del contador en Configuración)
    Route::get('/usuarios-conectados', [SessionController::class, 'connected'])->name('sesiones.conectadas');

    // Aceptación del aviso de uso aceptable (A.5.10 / A.5.34)
    Route::post('/aviso-uso/aceptar', [AupController::class, 'accept'])->name('aup.accept');
});

// Webhook público de Mailgun (sin auth ni CSRF; excluido en bootstrap/app.php).
Route::post('/webhooks/mailgun', [MailWebhookController::class, 'mailgun'])->name('webhooks.mailgun');
