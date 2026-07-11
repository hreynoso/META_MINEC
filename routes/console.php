<?php

use App\Models\Setting;
use App\Services\Backup\CloudBackupService;
use App\Services\Security\DependencyAudit;
use App\Services\Security\DependencyAuditReport;
use Illuminate\Support\Facades\Schedule;

// A.8.13 — Respaldo automático de la base de datos al almacenamiento en la nube
// configurado (Dropbox / Google Cloud) en Configuración → Respaldos. Se evalúa
// cada minuto y solo se ejecuta cuando coincide con la hora/periodicidad
// configuradas; toda la lógica de proveedor/credenciales vive en el servicio.
Schedule::call(function () {
    $backup = app(CloudBackupService::class);

    if (! $backup->enabled()) {
        return;
    }

    // La hora configurada se interpreta en UTC (hora del servidor), sin depender
    // de APP_TIMEZONE, para que coincida con lo mostrado a los administradores.
    if (now()->utc()->format('H:i') !== (string) Setting::value(CloudBackupService::TIME_KEY, '02:00')) {
        return;
    }

    // Periodicidad semanal: solo los lunes (UTC).
    if ((string) Setting::value(CloudBackupService::FREQUENCY_KEY, 'daily') === 'weekly' && ! now()->utc()->isMonday()) {
        return;
    }

    $backup->run();
})
    ->everyMinute()
    ->name('backup:auto')
    ->withoutOverlapping()
    ->onOneServer();

// A.8.10 — purga de la bitácora de actividad según la retención configurada.
Schedule::command('activitylog:clean')
    ->dailyAt('03:00')
    ->name('logs:clean')
    ->onOneServer();

// A.8.8 — análisis de dependencias programable. Cada día a las 04:00 se comprueba
// si toca el informe según la periodicidad configurada en Configuración →
// Seguridad → Dependencias (por defecto cada 30 días). Al vencer, se ejecuta el
// análisis, se genera el PDF y se notifica al equipo de seguridad por correo con
// el informe adjunto.
Schedule::call(function () {
    $audit = app(DependencyAudit::class);

    if (! $audit->dueForReport()) {
        return;
    }

    $audit->run();
    app(DependencyAuditReport::class)->send();
    $audit->markReported();
})
    ->dailyAt('04:00')
    ->name('security:audit-report')
    ->onOneServer();
