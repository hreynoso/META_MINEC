<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailEvent;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MailSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/MailSettings', [
            'settings' => [
                'provider' => Setting::value('mail.provider', 'mailgun'),
                'from_address' => Setting::value('mail.from_address', config('mail.from.address')),
                'from_name' => Setting::value('mail.from_name', config('mail.from.name')),
                'mg_domain' => Setting::value('mail.mg.domain', ''),
                'mg_region' => Setting::value('mail.mg.region', 'us'),
                'has_mg_secret' => filled(Setting::value('mail.mg.secret')),
                'has_mg_webhook' => filled(Setting::value('mail.mg.webhook_key')),
                'smtp_host' => Setting::value('mail.smtp.host', ''),
                'smtp_port' => Setting::value('mail.smtp.port', '587'),
                'smtp_username' => Setting::value('mail.smtp.username', ''),
                'smtp_encryption' => Setting::value('mail.smtp.encryption', 'tls'),
                'has_smtp_password' => filled(Setting::value('mail.smtp.password')),
            ],
            'events' => MailEvent::latest('occurred_at')->latest('id')->limit(50)->get()
                ->map(fn (MailEvent $e) => [
                    'id' => $e->id,
                    'event' => $e->event,
                    'severity' => $e->severity,
                    'recipient' => $e->recipient,
                    'reason' => $e->reason,
                    'date' => \App\Support\LocalTime::format($e->occurred_at, 'd/m/Y h:i a'),
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:mailgun,smtp'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string', 'max:255'],
            'mg_domain' => ['nullable', 'string', 'max:255'],
            'mg_region' => ['required', 'in:us,eu'],
            'mg_secret' => ['nullable', 'string', 'max:255'],
            'mg_webhook_key' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['required', 'in:tls,ssl,none'],
        ]);

        Setting::put('mail.provider', $data['provider']);
        Setting::put('mail.from_address', $data['from_address']);
        Setting::put('mail.from_name', $data['from_name']);
        Setting::put('mail.mg.domain', $data['mg_domain'] ?? '');
        Setting::put('mail.mg.region', $data['mg_region']);
        Setting::put('mail.smtp.host', $data['smtp_host'] ?? '');
        Setting::put('mail.smtp.port', (string) ($data['smtp_port'] ?? '587'));
        Setting::put('mail.smtp.username', $data['smtp_username'] ?? '');
        Setting::put('mail.smtp.encryption', $data['smtp_encryption']);

        // Secretos: solo se sobrescriben si el usuario escribió uno nuevo.
        if (filled($data['mg_secret'] ?? null)) {
            Setting::put('mail.mg.secret', $data['mg_secret']);
        }
        if (filled($data['mg_webhook_key'] ?? null)) {
            Setting::put('mail.mg.webhook_key', $data['mg_webhook_key']);
        }
        if (filled($data['smtp_password'] ?? null)) {
            Setting::put('mail.smtp.password', $data['smtp_password']);
        }

        return back()->with('success', __('messages.mail.updated'));
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->applyRuntimeConfig();

        $subject = $data['subject'] ?: '[META] Correo de prueba';
        $body = $data['message'] ?: 'Este es un correo de prueba enviado desde el panel de configuración de Sistema META.';

        try {
            Mail::raw($body, fn ($m) => $m->to($data['to'])->subject($subject));
        } catch (Throwable $e) {
            return back()->with('error', __('messages.mail.test_failed', ['error' => $e->getMessage()]));
        }

        return back()->with('success', __('messages.mail.test_sent', ['email' => $data['to']]));
    }

    /** Aplica la configuración guardada al mailer en tiempo de ejecución. */
    private function applyRuntimeConfig(): void
    {
        $provider = Setting::value('mail.provider', 'mailgun');

        config([
            'mail.from.address' => Setting::value('mail.from_address', config('mail.from.address')),
            'mail.from.name' => Setting::value('mail.from_name', config('mail.from.name')),
        ]);

        if ($provider === 'mailgun') {
            config([
                'mail.default' => 'mailgun',
                'services.mailgun.domain' => Setting::value('mail.mg.domain'),
                'services.mailgun.secret' => Setting::value('mail.mg.secret'),
                'services.mailgun.endpoint' => Setting::value('mail.mg.region', 'us') === 'eu' ? 'api.eu.mailgun.net' : 'api.mailgun.net',
            ]);

            return;
        }

        $encryption = Setting::value('mail.smtp.encryption', 'tls');
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => Setting::value('mail.smtp.host'),
            'mail.mailers.smtp.port' => (int) Setting::value('mail.smtp.port', 587),
            'mail.mailers.smtp.username' => Setting::value('mail.smtp.username'),
            'mail.mailers.smtp.password' => Setting::value('mail.smtp.password'),
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
        ]);
    }
}
