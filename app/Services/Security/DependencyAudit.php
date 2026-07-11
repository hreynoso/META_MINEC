<?php

namespace App\Services\Security;

use App\Models\Setting;
use Illuminate\Support\Carbon;
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

    /** Periodo (en días) entre análisis programados. Configurable en el apartado. */
    public const INTERVAL_KEY = 'security.deps_interval_days';

    /** Fecha del último informe notificado por correo al equipo de seguridad. */
    public const REPORTED_KEY = 'security.deps_reported_at';

    public const DEFAULT_INTERVAL_DAYS = 30;

    public const MIN_INTERVAL_DAYS = 1;

    public const MAX_INTERVAL_DAYS = 365;

    /** Periodo configurado (días), acotado a un rango razonable. */
    public function intervalDays(): int
    {
        $days = (int) Setting::value(self::INTERVAL_KEY, self::DEFAULT_INTERVAL_DAYS);

        if ($days < self::MIN_INTERVAL_DAYS) {
            return self::DEFAULT_INTERVAL_DAYS;
        }

        return min($days, self::MAX_INTERVAL_DAYS);
    }

    /** Guarda el periodo entre análisis programados (días). */
    public function setIntervalDays(int $days): void
    {
        $days = max(self::MIN_INTERVAL_DAYS, min($days, self::MAX_INTERVAL_DAYS));

        Setting::put(self::INTERVAL_KEY, (string) $days);
    }

    /** Fecha del último informe enviado al equipo de seguridad, o null. */
    public function reportedAt(): ?Carbon
    {
        $raw = Setting::value(self::REPORTED_KEY);

        return $raw ? rescue(fn () => Carbon::parse((string) $raw), null, false) : null;
    }

    /** Marca "ahora" como fecha del último informe notificado. */
    public function markReported(): void
    {
        Setting::put(self::REPORTED_KEY, now()->toIso8601String());
    }

    /** Próxima ejecución programada del informe (según el periodo configurado). */
    public function nextReportAt(): Carbon
    {
        $last = $this->reportedAt();

        return ($last ?? now())->copy()->addDays($this->intervalDays());
    }

    /** ¿Toca ya generar y notificar el informe programado? */
    public function dueForReport(): bool
    {
        $last = $this->reportedAt();

        return $last === null || $last->copy()->addDays($this->intervalDays())->isPast();
    }

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
