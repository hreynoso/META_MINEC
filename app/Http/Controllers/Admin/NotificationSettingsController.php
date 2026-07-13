<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendScheduledReport;
use App\Models\Setting;
use App\Services\Reports\ScheduledReports;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /** Interruptores de notificación y su valor por defecto. */
    private const TOGGLES = [
        'email_enabled' => true,
        'project_at_risk' => true,
        'project_updated' => false,
        'memoir_generated' => true,
        'weekly_digest' => false,
    ];

    public function edit(): Response
    {
        $toggles = [];
        foreach (self::TOGGLES as $key => $default) {
            $toggles[$key] = (bool) Setting::value("notify.{$key}", $default);
        }

        return Inertia::render('Admin/Notifications', [
            'settings' => $toggles,
            'recipients' => (string) Setting::value('notify.recipients', ''),
            'scheduled' => [
                'access_review' => [
                    'enabled' => (bool) Setting::value(ScheduledReports::ACCESS_ENABLED),
                    'interval_days' => (int) Setting::value(ScheduledReports::ACCESS_INTERVAL, ScheduledReports::DEFAULT_ACCESS_DAYS),
                    'recipients' => (string) Setting::value(ScheduledReports::ACCESS_RECIPIENTS, ''),
                    'last_sent_at' => $this->when(ScheduledReports::ACCESS_LAST),
                ],
                'risk_report' => [
                    'enabled' => (bool) Setting::value(ScheduledReports::RISK_ENABLED),
                    'frequency' => (string) Setting::value(ScheduledReports::RISK_FREQ, 'weekly'),
                    'time' => (string) Setting::value(ScheduledReports::RISK_TIME, '07:00'),
                    'recipients' => (string) Setting::value(ScheduledReports::RISK_RECIPIENTS, ''),
                    'last_sent_at' => $this->when(ScheduledReports::RISK_LAST),
                ],
                'minister_report' => [
                    'enabled' => (bool) Setting::value(ScheduledReports::MIN_ENABLED),
                    'frequency' => (string) Setting::value(ScheduledReports::MIN_FREQ, 'weekly'),
                    'time' => (string) Setting::value(ScheduledReports::MIN_TIME, '07:00'),
                    'recipients' => (string) Setting::value(ScheduledReports::MIN_RECIPIENTS, ''),
                    'last_sent_at' => $this->when(ScheduledReports::MIN_LAST),
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email_enabled' => ['boolean'],
            'project_at_risk' => ['boolean'],
            'project_updated' => ['boolean'],
            'memoir_generated' => ['boolean'],
            'weekly_digest' => ['boolean'],
            'recipients' => ['nullable', 'string', 'max:2000'],

            // Recordatorio de revisión de accesos.
            'access_review.enabled' => ['boolean'],
            'access_review.interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'access_review.recipients' => ['nullable', 'string', 'max:2000'],

            // Informe de riesgos (IA) e informe de la Ministra.
            'risk_report.enabled' => ['boolean'],
            'risk_report.frequency' => ['required', 'in:daily,weekly,monthly'],
            'risk_report.time' => ['required', 'date_format:H:i'],
            'risk_report.recipients' => ['nullable', 'string', 'max:2000'],
            'minister_report.enabled' => ['boolean'],
            'minister_report.frequency' => ['required', 'in:daily,weekly,monthly'],
            'minister_report.time' => ['required', 'date_format:H:i'],
            'minister_report.recipients' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach (array_keys(self::TOGGLES) as $key) {
            Setting::put("notify.{$key}", $request->boolean($key) ? '1' : '');
        }
        Setting::put('notify.recipients', $data['recipients'] ?? '');

        Setting::put(ScheduledReports::ACCESS_ENABLED, $request->boolean('access_review.enabled') ? '1' : '');
        Setting::put(ScheduledReports::ACCESS_INTERVAL, (string) $data['access_review']['interval_days']);
        Setting::put(ScheduledReports::ACCESS_RECIPIENTS, $data['access_review']['recipients'] ?? '');

        Setting::put(ScheduledReports::RISK_ENABLED, $request->boolean('risk_report.enabled') ? '1' : '');
        Setting::put(ScheduledReports::RISK_FREQ, $data['risk_report']['frequency']);
        Setting::put(ScheduledReports::RISK_TIME, $data['risk_report']['time']);
        Setting::put(ScheduledReports::RISK_RECIPIENTS, $data['risk_report']['recipients'] ?? '');

        Setting::put(ScheduledReports::MIN_ENABLED, $request->boolean('minister_report.enabled') ? '1' : '');
        Setting::put(ScheduledReports::MIN_FREQ, $data['minister_report']['frequency']);
        Setting::put(ScheduledReports::MIN_TIME, $data['minister_report']['time']);
        Setting::put(ScheduledReports::MIN_RECIPIENTS, $data['minister_report']['recipients'] ?? '');

        return back()->with('success', __('messages.notifications.updated'));
    }

    /** Genera y envía un informe programado de inmediato (prueba/bajo demanda). */
    public function sendNow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:access_review,risk,minister'],
        ]);

        SendScheduledReport::dispatch($data['type']);

        return back()->with('success', __('messages.notifications.report_queued'));
    }

    /** Fecha localizada de un ajuste ISO, o null. */
    private function when(string $key): ?string
    {
        $raw = Setting::value($key);

        return $raw ? LocalTime::format(Carbon::parse((string) $raw)) : null;
    }
}
