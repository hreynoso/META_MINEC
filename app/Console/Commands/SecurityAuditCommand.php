<?php

namespace App\Console\Commands;

use App\Services\Security\DependencyAudit;
use Illuminate\Console\Command;

/**
 * Ejecuta la auditoría de dependencias y guarda el resultado (A.8.8).
 * Programado semanalmente; también invocable manualmente.
 */
class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit';

    protected $description = 'Analiza vulnerabilidades conocidas en las dependencias (composer audit).';

    public function handle(DependencyAudit $audit): int
    {
        $result = $audit->run();

        if (! ($result['available'] ?? false)) {
            $this->warn('composer audit no está disponible en este entorno.');

            return self::SUCCESS;
        }

        $count = (int) ($result['count'] ?? 0);
        $count === 0
            ? $this->info('Sin vulnerabilidades conocidas.')
            : $this->error("{$count} vulnerabilidad(es) encontrada(s).");

        return self::SUCCESS;
    }
}
