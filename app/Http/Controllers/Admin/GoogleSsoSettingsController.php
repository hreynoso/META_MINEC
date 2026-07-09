<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\GoogleSso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GoogleSsoSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/GoogleSsoSettings', [
            'settings' => [
                'client_id' => (string) GoogleSso::clientId(),
                'redirect' => (string) GoogleSso::redirectUri(),
                'hosted_domain' => (string) GoogleSso::hostedDomain(),
                // El secreto nunca se envía al cliente; solo si ya existe uno.
                'has_secret' => filled(GoogleSso::clientSecret()),
                'configured' => GoogleSso::isConfigured(),
            ],
            // URL de retorno que TI debe autorizar en Google Cloud Console.
            'callbackUrl' => route('google.callback'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'redirect' => ['nullable', 'url', 'max:255'],
            'hosted_domain' => ['nullable', 'string', 'max:255'],
        ], [], [
            'client_id' => 'ID de cliente',
            'client_secret' => 'secreto de cliente',
            'redirect' => 'URL de retorno',
            'hosted_domain' => 'dominio de Workspace',
        ]);

        Setting::put('sso.google.client_id', $validated['client_id'] ?? '');
        Setting::put('sso.google.redirect', $validated['redirect'] ?? '');
        Setting::put('sso.google.hosted_domain', $validated['hosted_domain'] ?? '');

        // El secreto solo se actualiza si el usuario escribió uno nuevo.
        if (filled($validated['client_secret'] ?? null)) {
            Setting::put('sso.google.client_secret', $validated['client_secret']);
        }

        return back()->with('success', __('messages.sso.updated'));
    }
}
