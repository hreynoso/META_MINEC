<?php

namespace App\Jobs;

use App\Services\Backup\CloudBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ejecuta un respaldo de la base de datos en segundo plano (cola Horizon), para
 * no bloquear la petición web ni el planificador. El propio servicio registra el
 * resultado en el historial y notifica a los Super Admin si falla (A.8.13).
 */
class RunBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tiempo máximo de ejecución (dump + subida). */
    public int $timeout = 900;

    /** No se reintenta para evitar respaldos duplicados ante un fallo transitorio. */
    public int $tries = 1;

    public function handle(CloudBackupService $backup): void
    {
        // manual: true → ejecuta la copia; la decisión de respaldar ya se tomó
        // (botón bajo demanda o planificador tras validar hora/periodicidad).
        $backup->run(manual: true);
    }
}
