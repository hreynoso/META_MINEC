<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A.8.29 — Pruebas automatizadas de seguridad: autoevaluación de la postura de
 * seguridad del sistema. Ejecuta comprobaciones sobre la configuración y el
 * estado de las cuentas y reporta OK / advertencia / crítico. Los textos se
 * traducen en el frontend (namespace i18n `security`).
 */
class SecurityController extends Controller
{
    public function index(): Response
    {
        $checks = $this->runChecks();

        $summary = ['ok' => 0, 'warn' => 0, 'fail' => 0, 'info' => 0];
        foreach ($checks as $c) {
            $summary[$c['status']] = ($summary[$c['status']] ?? 0) + 1;
        }

        return Inertia::render('Admin/Security', [
            'checks' => $checks,
            'summary' => $summary,
        ]);
    }

    /** @return array<int, array{key:string, status:string, params:array}> */
    private function runChecks(): array
    {
        $checks = [];

        // Cifrado en tránsito (HSTS).
        $checks[] = $this->c('https', config('security.hsts.enabled', true) ? 'ok' : 'warn');

        // Content-Security-Policy.
        $cspEnabled = (bool) config('security.csp.enabled', true);
        $cspReport = (bool) config('security.csp.report_only', false);
        $checks[] = $this->c('csp', ! $cspEnabled ? 'fail' : ($cspReport ? 'warn' : 'ok'));

        // Modo depuración.
        $checks[] = $this->c('debug', config('app.debug') ? 'fail' : 'ok');

        // Dominio SSO restringido.
        $domain = (string) config('services.google.hosted_domain');
        $checks[] = $this->c('sso_domain', $domain !== '' ? 'ok' : 'warn', ['domain' => $domain !== '' ? $domain : '—']);

        // Sesión única por usuario.
        $checks[] = $this->c('single_device', config('security.single_device.enabled', true) ? 'ok' : 'warn');

        // Cierre por inactividad.
        $idle = (bool) config('security.session_idle.enabled', true);
        $checks[] = $this->c('idle', $idle ? 'ok' : 'warn', ['minutes' => (int) config('security.session_idle.minutes', 30)]);

        // Política de contraseñas.
        $min = (int) config('security.password_policy.min', 12);
        $checks[] = $this->c('password', $min >= 12 ? 'ok' : 'warn', ['min' => $min]);

        // Aviso de uso aceptable.
        $checks[] = $this->c('aup', config('security.aup.enabled', true) ? 'ok' : 'warn');

        // Cuentas administrativas.
        $admins = (int) rescue(fn () => User::role(['Super Admin', 'Administrador'])->count(), 0, false);
        $checks[] = $this->c('admins', $admins > 0 ? 'ok' : 'fail', ['count' => $admins]);

        // Rol por defecto (solo lectura).
        $default = (string) config('security.default_role');
        $checks[] = $this->c('default_role', $default !== '' ? 'ok' : 'warn', ['role' => $default !== '' ? $default : '—']);

        // Respaldos automáticos.
        $hasBackup = filled(config('services.dropbox.token') ?? env('DROPBOX_BACKUP_TOKEN'));
        $checks[] = $this->c('backups', $hasBackup ? 'ok' : 'warn');

        // Retención de bitácora.
        $checks[] = $this->c('log_retention', 'ok', ['days' => (int) config('security.log_retention_days', 365)]);

        // Estado de cuentas (informativo).
        $blocked = (int) rescue(fn () => User::whereNotNull('blocked_at')->count(), 0, false);
        $dormant = (int) rescue(fn () => User::whereNotNull('last_login_at')
            ->where('last_login_at', '<', now()->subDays(90))->count(), 0, false);
        $checks[] = $this->c('accounts', 'info', ['blocked' => $blocked, 'dormant' => $dormant]);

        return $checks;
    }

    /** @param array<string, mixed> $params */
    private function c(string $key, string $status, array $params = []): array
    {
        return ['key' => $key, 'status' => $status, 'params' => (object) $params];
    }
}
