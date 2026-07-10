<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Services\Security\DependencyAudit;
use App\Support\ExportName;
use App\Support\LocalTime;
use App\Support\SheetExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Configuración → Seguridad. Agrupa los controles de seguridad:
 *  - Pruebas automatizadas (autoevaluación de postura, A.8.29)
 *  - Revisión de accesos (A.5.18)
 *  - Análisis de dependencias (A.8.8)
 */
class SecurityController extends Controller
{
    private const REVIEW_KEY = 'security.access_review';

    private const DORMANT_DAYS = 90;

    // ── A.8.29 Pruebas automatizadas ──────────────────────────────────────

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

    // ── A.5.18 Revisión de accesos ────────────────────────────────────────

    public function accessReview(): Response
    {
        return Inertia::render('Admin/SecurityAccess', [
            'users' => $this->reviewRows(),
            'lastReview' => $this->lastReview(),
        ]);
    }

    /** Registra una atestación de revisión de accesos (evidencia A.5.18). */
    public function recordReview(Request $request): RedirectResponse
    {
        $by = $request->user()?->name ?: ($request->user()?->email ?? 'Sistema');

        Setting::put(self::REVIEW_KEY, json_encode(['at' => now()->toIso8601String(), 'by' => $by]));

        rescue(fn () => activity('security')
            ->causedBy($request->user())
            ->log('Revisión de accesos registrada'), null, false);

        return back()->with('success', __('messages.security.review_recorded'));
    }

    public function accessExport(): StreamedResponse
    {
        $rows = collect($this->reviewRows())->map(fn (array $u) => [
            $u['name'],
            $u['email'],
            $u['institution'] ?? '',
            implode(', ', $u['roles']),
            $u['blocked'] ? 'Bloqueado' : 'Activo',
            $u['privileged'] ? 'Sí' : 'No',
            $u['last_login'] ?? ($u['never'] ? 'Nunca' : '—'),
            $u['dormant'] ? 'Sí' : 'No',
        ])->all();

        return SheetExport::stream(
            ExportName::make('Revision de accesos', 'xlsx'),
            ['Nombre', 'Correo', 'Institución', 'Roles', 'Estado', 'Privilegiado', 'Último acceso', 'Inactivo 90+ días'],
            $rows,
        );
    }

    // ── A.8.16 Alertas de seguridad ───────────────────────────────────────

    public function alerts(): Response
    {
        return Inertia::render('Admin/SecurityAlerts', [
            'settings' => [
                'enabled' => \App\Support\SecurityAlert::enabled(),
                'recipients' => \App\Support\SecurityAlert::configuredRecipients(),
            ],
        ]);
    }

    public function updateAlerts(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
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

        Setting::put(\App\Support\SecurityAlert::ENABLED_KEY, $request->boolean('enabled') ? '1' : '');
        Setting::put(\App\Support\SecurityAlert::RECIPIENTS_KEY, $emails->implode(', '));

        return back()->with('success', __('messages.security.alerts_saved'));
    }

    // ── A.8.8 Análisis de dependencias ────────────────────────────────────

    public function dependencies(DependencyAudit $audit): Response
    {
        return Inertia::render('Admin/SecurityDeps', [
            'audit' => $this->formatAudit($audit->latest()),
        ]);
    }

    public function runDependencies(DependencyAudit $audit): RedirectResponse
    {
        $audit->run();

        return back();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private function reviewRows(): array
    {
        $threshold = now()->subDays(self::DORMANT_DAYS);

        return User::with(['roles:id,name', 'institution:id,short_name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) use ($threshold) {
                $roles = $u->roles->pluck('name');
                $never = $u->last_login_at === null;

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'institution' => $u->institution?->short_name,
                    'roles' => $roles->all(),
                    'blocked' => $u->blocked_at !== null,
                    'privileged' => (bool) $roles->intersect(['Super Admin', 'Administrador'])->count(),
                    'last_login' => LocalTime::format($u->last_login_at),
                    'never' => $never,
                    'dormant' => $never || $u->last_login_at->lt($threshold),
                ];
            })
            ->all();
    }

    private function lastReview(): ?array
    {
        $raw = Setting::value(self::REVIEW_KEY);

        if (! $raw) {
            return null;
        }

        $data = json_decode((string) $raw, true);

        if (! is_array($data) || empty($data['at'])) {
            return null;
        }

        return [
            'at' => LocalTime::format(\Illuminate\Support\Carbon::parse($data['at'])),
            'by' => $data['by'] ?? '—',
        ];
    }

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

        $hasBackup = filled(config('services.dropbox.token') ?? env('DROPBOX_BACKUP_TOKEN'));
        $checks[] = $this->c('backups', $hasBackup ? 'ok' : 'warn');

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
