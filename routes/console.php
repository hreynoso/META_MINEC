<?php

use App\Services\Backup\DropboxBackupService;
use Illuminate\Support\Facades\Schedule;

// Backup diario 02:00 (A.12.3)
Schedule::call(fn () => app(DropboxBackupService::class)->run())
    ->dailyAt('02:00')
    ->name('backup:daily')
    ->onOneServer();
