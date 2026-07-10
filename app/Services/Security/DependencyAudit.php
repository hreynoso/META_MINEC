<?php

namespace App\Services\Security;

use App\Models\Setting;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Auditoría de dependencias (ISO 27001 A.8.8). Ejecuta `composer audit` y guarda
 * el último resultado en Settings para mostrarlo en Configuración → Seguridad.
 * El análisis autoritativo corre además en el pipeline de CI (npm + composer).
 * Si la herramienta no está disponible en el entorno de ejecución, se reporta
 * como "no disponible" sin romper nada.
 */
class DependencyAudit
{
    public const SETTING_KEY = 'security.deps_audit';

    /** Ejecuta el análisis y guarda el resultado. Devuelve el resultado. */
    public function run(): array
    {
        $result = $this->runComposerAudit();
        $result['ran_at'] = now()->toIso8601String();

        Setting::put(self::SETTING_KEY, json_encode($result));

        return $result;
    }

    /** Último resultado almacenado (o estado "nunca ejecutado"). */
    public function latest(): array
    {
        $raw = Setting::value(self::SETTING_KEY);

        if (! $raw) {
            return ['available' => true, 'ran_at' => null, 'advisories' => [], 'count' => 0];
        }

        $data = json_decode((string) $raw, true);

        return is_array($data) ? $data : ['available' => true, 'ran_at' => null, 'advisories' => [], 'count' => 0];
    }

    private function runComposerAudit(): array
    {
        try {
            $process = Process::fromShellCommandline(
                'composer audit --format=json --no-interaction 2>/dev/null',
                base_path(),
            );
            $process->setTimeout(120);
            $process->run();

            $data = json_decode($process->getOutput(), true);

            if (! is_array($data)) {
                return ['available' => false, 'advisories' => [], 'count' => 0];
            }

            $advisories = [];
            foreach (($data['advisories'] ?? []) as $package => $items) {
                foreach ((array) $items as $a) {
                    $advisories[] = [
                        'package' => $a['packageName'] ?? (string) $package,
                        'title' => $a['title'] ?? '',
                        'cve' => $a['cve'] ?? ($a['advisoryId'] ?? ''),
                        'severity' => strtolower((string) ($a['severity'] ?? '')),
                        'link' => $a['link'] ?? '',
                    ];
                }
            }

            return ['available' => true, 'advisories' => $advisories, 'count' => count($advisories)];
        } catch (Throwable $e) {
            return ['available' => false, 'advisories' => [], 'count' => 0];
        }
    }
}
