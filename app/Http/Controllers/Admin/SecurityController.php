<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\DependencyAudit;
use App\Services\Security\DependencyAuditReport;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración → Seguridad. Agrupa los controles de seguridad:
 *  - Pruebas automatizadas (autoevaluación de postura, A.8.29)
 *  - Revisión de accesos (A.5.18)
 *  - Análisis de dependencias (A.8.8)
 */
class SecurityController extends Controller
{
    private const DORMANT_DAYS = 90;

    // ── A.8.29 Pruebas automatizadas ──────────────────────────────────────
    // Nota: la revisión de accesos (A.5.18) vive ahora en Configuración →
    // Usuarios (App\Http\Controllers\Admin\UserController).

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

    // ── A.8.16 Alertas de seguridad ───────────────────────────────────────

    public function alerts(): Response
    {
        return Inertia::render('Admin/SecurityAlerts', [
            'settings' => [
                // Las alertas están SIEMPRE activas y no pueden desactivarse.
                'recipients' => \App\Support\SecurityAlert::configuredRecipients(),
            ],
        ]);
    }

    public function updateAlerts(Request $request): RedirectResponse
    {
        // Nota: no se acepta ningún parámetro para desactivar las alertas; están
        // permanentemente activas (A.8.16). Solo se configuran los destinatarios.
        $data = $request->validate([
            'recipients' => ['nullable', 'string', 'max:2000'],
        ]);

        // Cada correo separado por coma debe ser válido.
        $emails = collect(explode(',', (string) ($data['recipients'] ?? '')))
            ->map(fn ($e) => trim($e))
            ->filter();

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['recipients' => __('messages.security.invalid_email', ['email' => $email])]);
            }
        }

        Setting::put(\App\Support\SecurityAlert::RECIPIENTS_KEY, $emails->implode(', '));

        \App\Support\SecurityAlert::configChanged('Alertas de seguridad (destinatarios)');

        return back()->with('success', __('messages.security.alerts_saved'));
    }

    // ── A.8.8 Análisis de dependencias ────────────────────────────────────

    public function dependencies(DependencyAudit $audit): Response
    {
        return Inertia::render('Admin/SecurityDeps', [
            'audit' => $this->formatAudit($audit->latest()),
            'schedule' => [
                'interval_days' => $audit->intervalDays(),
                'reported_at' => LocalTime::format($audit->reportedAt()),
                'next_report_at' => LocalTime::format($audit->nextReportAt()),
                'recipients' => \App\Support\SecurityAlert::recipients(),
            ],
        ]);
    }

    public function runDependencies(DependencyAudit $audit): RedirectResponse
    {
        $audit->run();

        return back()->with('success', __('messages.security.deps_run'));
    }

    /** Guarda la periodicidad del análisis programado (A.8.8). */
    public function updateDependencySchedule(Request $request, DependencyAudit $audit): RedirectResponse
    {
        $data = $request->validate([
            'interval_days' => ['required', 'integer', 'min:'.DependencyAudit::MIN_INTERVAL_DAYS, 'max:'.DependencyAudit::MAX_INTERVAL_DAYS],
        ], [], ['interval_days' => 'periodicidad']);

        $audit->setIntervalDays((int) $data['interval_days']);

        \App\Support\SecurityAlert::configChanged('Análisis de dependencias (periodicidad del análisis programado)');

        return back()->with('success', __('messages.security.deps_schedule_saved'));
    }

    /** Descarga el informe PDF del último análisis de dependencias. */
    public function downloadDependencyReport(DependencyAuditReport $report): HttpResponse
    {
        return $report->pdf()->download($report->filename());
    }

    /** Genera el informe y lo envía por correo al equipo de seguridad (bajo demanda). */
    public function sendDependencyReport(DependencyAudit $audit, DependencyAuditReport $report): RedirectResponse
    {
        $audit->run();
        $sent = $report->send();
        $audit->markReported();

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? __('messages.security.deps_report_sent') : __('messages.security.deps_report_no_recipients'),
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatAudit(array $audit): array
    {
        $ranAt = $audit['ran_at'] ?? null;

        return [
            'available' => (bool) ($audit['available'] ?? true),
            'count' => (int) ($audit['count'] ?? 0),
            'advisories' => $audit['advisories'] ?? [],
            'ran_at' => $ranAt ? LocalTime::format(\Illuminate\Support\Carbon::parse($ranAt)) : null,
        ];
    }

    /** @return array<int, array{key:string, status:string, params:object}> */
    private function runChecks(): array
    {
        $checks = [];

        $checks[] = $this->c('https', config('security.hsts.enabled', true) ? 'ok' : 'warn');

        $cspEnabled = (bool) config('security.csp.enabled', true);
        $cspReport = (bool) config('security.csp.report_only', false);
        $checks[] = $this->c('csp', ! $cspEnabled ? 'fail' : ($cspReport ? 'warn' : 'ok'));

        $checks[] = $this->c('debug', config('app.debug') ? 'fail' : 'ok');

        $domain = (string) config('services.google.hosted_domain');
        $checks[] = $this->c('sso_domain', $domain !== '' ? 'ok' : 'warn', ['domain' => $domain !== '' ? $domain : '—']);

        $checks[] = $this->c('single_device', config('security.single_device.enabled', true) ? 'ok' : 'warn');

        $idle = (bool) config('security.session_idle.enabled', true);
        $checks[] = $this->c('idle', $idle ? 'ok' : 'warn', ['minutes' => (int) config('security.session_idle.minutes', 30)]);

        $min = (int) config('security.password_policy.min', 12);
        $checks[] = $this->c('password', $min >= 12 ? 'ok' : 'warn', ['min' => $min]);

        $checks[] = $this->c('aup', config('security.aup.enabled', true) ? 'ok' : 'warn');

        $admins = (int) rescue(fn () => User::role(['Super Admin', 'Administrador'])->count(), 0, false);
        $checks[] = $this->c('admins', $admins > 0 ? 'ok' : 'fail', ['count' => $admins]);

        $default = (string) config('security.default_role');
        $checks[] = $this->c('default_role', $default !== '' ? 'ok' : 'warn', ['role' => $default !== '' ? $default : '—']);

        // A.8.13 — respaldos automáticos: activos y con credenciales del proveedor.
        $backupEnabled = (bool) Setting::value(\App\Services\Backup\CloudBackupService::ENABLED_KEY);
        $backupProvider = (string) Setting::value(\App\Services\Backup\CloudBackupService::PROVIDER_KEY, 'dropbox');
        $backupCreds = $backupProvider === 'google_cloud'
            ? filled(Setting::value(\App\Services\Backup\CloudBackupService::GCS_CREDENTIALS_KEY)) && filled(Setting::value(\App\Services\Backup\CloudBackupService::GCS_BUCKET_KEY))
            : filled(Setting::value(\App\Services\Backup\CloudBackupService::DROPBOX_TOKEN_KEY));
        $checks[] = $this->c('backups', ($backupEnabled && $backupCreds) ? 'ok' : 'warn');

        $checks[] = $this->c('log_retention', 'ok', ['days' => (int) config('security.log_retention_days', 365)]);

        $blocked = (int) rescue(fn () => User::whereNotNull('blocked_at')->count(), 0, false);
        $dormant = (int) rescue(fn () => User::whereNotNull('last_login_at')
            ->where('last_login_at', '<', now()->subDays(self::DORMANT_DAYS))->count(), 0, false);
        $checks[] = $this->c('accounts', 'info', ['blocked' => $blocked, 'dormant' => $dormant]);

        return $checks;
    }

    /** @param array<string, mixed> $params */
    private function c(string $key, string $status, array $params = []): array
    {
        return ['key' => $key, 'status' => $status, 'params' => (object) $params];
    }
}
