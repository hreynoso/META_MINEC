<?php

use App\Services\Backup\DropboxBackupService;
use Illuminate\Support\Facades\Schedule;

// Backup diario 02:00 (A.12.3)
Schedule::call(fn () => app(DropboxBackupService::class)->run())
    ->dailyAt('02:00')
    ->name('backup:daily')
    ->onOneServer();

// A.8.10 — purga de la bitácora de actividad según la retención configurada.
Schedule::command('activitylog:clean')
    ->dailyAt('03:00')
    ->name('logs:clean')
    ->onOneServer();

// A.8.8 — auditoría de dependencias semanal (composer audit).
Schedule::command('security:audit')
    ->weeklyOn(1, '04:00')
    ->name('security:audit')
    ->onOneServer();
