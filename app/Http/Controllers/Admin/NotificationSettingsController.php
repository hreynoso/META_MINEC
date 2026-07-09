<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);

        foreach (array_keys(self::TOGGLES) as $key) {
            Setting::put("notify.{$key}", $request->boolean($key) ? '1' : '');
        }

        Setting::put('notify.recipients', $data['recipients'] ?? '');

        return back()->with('success', __('messages.notifications.updated'));
    }
}
