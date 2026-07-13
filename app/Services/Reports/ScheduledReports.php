<?php

namespace App\Services\Reports;

use App\Models\Institution;
use App\Models\Project;
use App\Models\Setting;
use App\Models\User;
use App\Services\Ai\AiReportService;
use App\Services\PredictionService;
use App\Support\Branding;
use App\Support\ExportName;
use App\Support\LocalTime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envíos programados por correo (informes recurrentes en PDF, A.5.18 / gestión).
 * - Recordatorio de revisión de usuarios y accesos (cada N días).
 * - Informe de riesgos con IA predictiva (diario/semanal/mensual).
 * - Informe de la Ministra con IA (diario/semanal/mensual).
 * Destinatarios = usuarios por rol + correos adicionales configurables.
 * Todo va en cola (App\Jobs\SendScheduledReport) y nunca rompe el flujo.
 */
class ScheduledReports
{
    // ── Claves de configuración (tabla settings) ────────────────────────────
    public const ACCESS_ENABLED = 'notify.access_review.enabled';
    public const ACCESS_INTERVAL = 'notify.access_review.interval_days';
    public const ACCESS_RECIPIENTS = 'notify.access_review.recipients';
    public const ACCESS_LAST = 'notify.access_review.last_sent_at';

    public const RISK_ENABLED = 'notify.risk_report.enabled';
    public const RISK_FREQ = 'notify.risk_report.frequency';
    public const RISK_TIME = 'notify.risk_report.time';
    public const RISK_RECIPIENTS = 'notify.risk_report.recipients';
    public const RISK_LAST = 'notify.risk_report.last_sent_at';

    public const MIN_ENABLED = 'notify.minister_report.enabled';
    public const MIN_FREQ = 'notify.minister_report.frequency';
    public const MIN_TIME = 'notify.minister_report.time';
    public const MIN_RECIPIENTS = 'notify.minister_report.recipients';
    public const MIN_LAST = 'notify.minister_report.last_sent_at';

    /** Hora fija (UTC) a la que se evalúa el recordatorio de revisión de accesos. */
    private const ACCESS_HOUR = '13:00';

    public const DEFAULT_ACCESS_DAYS = 90;

    /** Roles destinatarios por informe (además de los correos adicionales). */
    private const ACCESS_ROLES = ['Super Admin'];
    private const RISK_ROLES = ['Analista', 'Gestor de Proyectos'];
    private const MIN_ROLES = ['Directivo'];

    public function __construct(
        private readonly MinisterReportData $data,
        private readonly PredictionService $pred,
        private readonly AiReportService $ai,
    ) {}

    // ── ¿Toca ejecutar? (se evalúan cada minuto desde el planificador) ───────

    public function dueAccessReview(): bool
    {
        if (! Setting::value(self::ACCESS_ENABLED) || now()->utc()->format('H:i') !== self::ACCESS_HOUR) {
            return false;
        }

        $last = Setting::value(self::ACCESS_LAST);
        $days = $this->accessIntervalDays();

        return ! $last || rescue(
            fn () => Carbon::parse((string) $last)->addDays($days)->lte(now()),
            true,
            false,
        );
    }

    public function dueRiskReport(): bool
    {
        return $this->dueFrequency(self::RISK_ENABLED, self::RISK_FREQ, self::RISK_TIME);
    }

    public function dueMinisterReport(): bool
    {
        return $this->dueFrequency(self::MIN_ENABLED, self::MIN_FREQ, self::MIN_TIME);
    }

    private function dueFrequency(string $enabledKey, string $freqKey, string $timeKey): bool
    {
        if (! Setting::value($enabledKey)) {
            return false;
        }

        if (now()->utc()->format('H:i') !== (string) Setting::value($timeKey, '07:00')) {
            return false;
        }

        return match ((string) Setting::value($freqKey, 'weekly')) {
            'daily' => true,
            'monthly' => now()->utc()->day === 1,
            default => now()->utc()->isMonday(),
        };
    }

    public function accessIntervalDays(): int
    {
        $days = (int) Setting::value(self::ACCESS_INTERVAL, self::DEFAULT_ACCESS_DAYS);

        return $days >= 1 ? min($days, 3650) : self::DEFAULT_ACCESS_DAYS;
    }

    // ── Envíos ───────────────────────────────────────────────────────────────

    /** Recordatorio de revisión de usuarios y accesos (A.5.18). */
    public function sendAccessReviewReminder(): void
    {
        $to = $this->recipients(self::ACCESS_ROLES, self::ACCESS_RECIPIENTS);

        if (empty($to)) {
            Log::warning('notify: recordatorio de accesos sin destinatarios');

            return;
        }

        $threshold = now()->subDays(90);
        $total = rescue(fn () => User::count(), 0, false);
        $privileged = rescue(fn () => User::role(['Super Admin', 'Administrador'])->count(), 0, false);
        $blocked = rescue(fn () => User::whereNotNull('blocked_at')->count(), 0, false);
        $dormant = rescue(fn () => User::whereNotNull('last_login_at')->where('last_login_at', '<', $threshold)->count(), 0, false);

        $body = "Recordatorio: toca revisar los usuarios y sus accesos en el Sistema META (ISO 27001 A.5.18).\n\n"
            ."Situación actual:\n"
            ."- Usuarios totales: {$total}\n"
            ."- Con rol privilegiado (Super Admin/Administrador): {$privileged}\n"
            ."- Bloqueados: {$blocked}\n"
            ."- Inactivos (90+ días): {$dormant}\n\n"
            ."Entra a Configuración → Usuarios para revisar accesos, bloquear/desbloquear cuentas y registrar la revisión.\n"
            ."Periodicidad configurada: cada {$this->accessIntervalDays()} días.";

        rescue(fn () => Mail::raw($body, fn ($m) => $m->to($to)
            ->subject('META · Recordatorio: revisión de usuarios y accesos')), null, false);

        Setting::put(self::ACCESS_LAST, now()->toIso8601String());
    }

    /** Informe de riesgos con IA predictiva (cartera consolidada en riesgo). */
    public function sendRiskReport(): void
    {
        $to = $this->recipients(self::RISK_ROLES, self::RISK_RECIPIENTS);

        if (empty($to)) {
            Log::warning('notify: informe de riesgos sin destinatarios');

            return;
        }

        $atRisk = Project::with('institution')->get()
            ->map(fn (Project $p) => ['project' => $p, 'score' => $this->pred->score($p)])
            ->filter(fn (array $r) => $r['score'] < 60)
            ->sortBy('score')
            ->take(25)
            ->values();

        $rows = $atRisk->map(function (array $r) {
            /** @var Project $p */
            $p = $r['project'];

            return [
                $p->code.' — '.$p->name,
                $p->institution?->short_name ?? '—',
                $p->physical_progress.'%',
                MinisterReportData::RISK_LABEL[$p->risk_level] ?? $p->risk_level,
                $r['score'].'%',
                $this->aiRecommendation($p),
            ];
        })->all();

        $pdf = Pdf::loadView('reports.table', [
            'title' => 'Informe de riesgos — IA predictiva',
            'subtitle' => 'Proyectos en riesgo (probabilidad de éxito < 60%) con recomendaciones de IA.',
            'generated_at' => LocalTime::format(now(), 'd/m/Y H:i'),
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'columns' => ['Proyecto', 'Institución', 'Avance físico', 'Riesgo', '% Éxito', 'Recomendación (IA)'],
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        $count = count($rows);
        $body = "Informe automático de riesgos (IA predictiva) del Sistema META.\n\n"
            .($count > 0
                ? "Se adjunta el PDF con {$count} proyecto(s) en riesgo y sus recomendaciones."
                : 'No hay proyectos en riesgo en este momento; se adjunta el informe.')
            ."\n\nFecha (UTC): ".now()->utc()->toDateTimeString();

        $this->mailPdf($to, 'META · Informe de riesgos (IA predictiva)', $body, $pdf->output(), ExportName::make('Informe de riesgos', 'pdf'));

        Setting::put(self::RISK_LAST, now()->toIso8601String());
    }

    /** Informe de la Ministra con IA (tablero + narrativa) en PDF. */
    public function sendMinisterReport(): void
    {
        $to = $this->recipients(self::MIN_ROLES, self::MIN_RECIPIENTS);

        if (empty($to)) {
            Log::warning('notify: informe de la Ministra sin destinatarios');

            return;
        }

        $days = match ((string) Setting::value(self::MIN_FREQ, 'weekly')) {
            'daily' => 1,
            'monthly' => 30,
            default => 7,
        };
        $to_date = now()->toDateString();
        $from_date = now()->subDays($days)->toDateString();
        $institutionIds = Institution::has('projects')->pluck('id')->all();

        $narrative = '';
        if ($this->ai->isConfigured() && ! empty($institutionIds)) {
            $narrative = rescue(
                fn () => trim($this->ai->generate($this->data->buildPrompt($institutionIds, $from_date, $to_date))),
                '',
                false,
            );
        }

        if ($narrative === '') {
            $narrative = "## Informe automático\n\nLa narrativa con IA no está disponible (IA no configurada o sin respuesta). "
                ."Se incluye el tablero con los datos actuales de la cartera.";
        }

        $selected = Institution::whereIn('id', $institutionIds)->orderBy('name')->pluck('short_name')->all();

        $pdf = Pdf::loadView('reports.minister', [
            'logo' => Branding::dataUri('logo_login'),
            'institution' => config('branding.institution'),
            'generated_at' => LocalTime::format(now()),
            'from' => $from_date,
            'to' => $to_date,
            'selected' => $selected,
            'summary' => $this->data->summaryData(),
            'kpis' => $this->data->kpisData(),
            'byInstitution' => $this->data->semaforo(),
            'alerts' => $this->data->alerts(),
            'recommendations' => $this->data->recommendations(),
            'narrative' => $narrative,
        ])->setPaper('a4');

        $body = "Informe automático de la Ministra del Sistema META.\n\n"
            ."Período: {$from_date} al {$to_date}.\n"
            ."Se adjunta el informe ejecutivo en PDF.\n\nFecha (UTC): ".now()->utc()->toDateTimeString();

        $this->mailPdf($to, 'META · Informe de la Ministra', $body, $pdf->output(), ExportName::make('Informe de la Ministra', 'pdf'));

        Setting::put(self::MIN_LAST, now()->toIso8601String());
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Recomendación de IA para un proyecto; cae a la heurística si no hay IA. */
    private function aiRecommendation(Project $p): string
    {
        if (! $this->ai->isConfigured()) {
            return $this->pred->recommendation($p);
        }

        return rescue(function () use ($p) {
            $factors = implode('; ', $this->pred->factors($p));
            $score = $this->pred->score($p);
            $prompt = "Eres analista de inversión pública del MINEC de El Salvador. "
                ."Proyecto {$p->name} ({$p->code}), institución {$p->institution?->short_name}. "
                ."Avance físico {$p->physical_progress}%, ejecución {$p->financialProgress()}%, riesgo {$p->risk_level}, "
                ."probabilidad de éxito {$score}%. Factores: {$factors}. "
                ."Redacta en 1 o 2 frases una recomendación concreta y accionable en español, sin encabezados.";

            $text = trim($this->ai->generate($prompt));

            return $text !== '' ? $text : $this->pred->recommendation($p);
        }, $this->pred->recommendation($p), false);
    }

    /**
     * Destinatarios = correos de usuarios activos con los roles indicados +
     * correos adicionales configurados. En minúsculas y sin duplicados.
     *
     * @param  string[]  $roles
     * @return string[]
     */
    private function recipients(array $roles, string $extraKey): array
    {
        $byRole = rescue(
            fn () => User::role($roles)->whereNull('blocked_at')->pluck('email')->all(),
            [],
            false,
        );

        $extra = $this->parseEmails((string) Setting::value($extraKey, ''));

        return array_values(array_unique(array_filter(array_map(
            fn ($e) => strtolower(trim((string) $e)),
            array_merge($byRole, $extra),
        ))));
    }

    /** @return string[] */
    private function parseEmails(string $csv): array
    {
        return array_filter(array_map('trim', preg_split('/[,;\s]+/', $csv) ?: []));
    }

    private function mailPdf(array $to, string $subject, string $body, string $pdf, string $filename): void
    {
        rescue(fn () => Mail::raw($body, function ($m) use ($to, $subject, $pdf, $filename) {
            $m->to($to)->subject($subject)->attachData($pdf, $filename, ['mime' => 'application/pdf']);
        }), null, false);
    }
}
