<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function edit(): Response
    {
        $key = Setting::value('ai.api_key');

        return Inertia::render('Admin/AiSettings', [
            'settings' => [
                'provider' => Setting::value('ai.provider', 'anthropic'),
                'model' => Setting::value('ai.model', ''),
                'enabled' => (bool) Setting::value('ai.enabled', false),
                // Nunca se envía la clave completa al cliente; solo si existe.
                'has_key' => filled($key),
                // Credenciales específicas de Google Gemini (correo/contraseña).
                'gemini_email' => (string) Setting::value('ai.gemini_email', ''),
                'has_gemini_password' => filled(Setting::value('ai.gemini_password')),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:anthropic,gemini,openai'],
            'model' => ['nullable', 'string', 'max:100'],
            'enabled' => ['boolean'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'gemini_email' => ['nullable', 'email', 'max:255'],
            'gemini_password' => ['nullable', 'string', 'max:255'],
        ], [], [
            'provider' => 'proveedor',
            'model' => 'modelo',
            'api_key' => 'clave del API',
            'gemini_email' => 'correo',
            'gemini_password' => 'contraseña',
        ]);

        Setting::put('ai.provider', $validated['provider']);
        Setting::put('ai.model', $validated['model'] ?? '');
        Setting::put('ai.enabled', $request->boolean('enabled') ? '1' : '');
        Setting::put('ai.gemini_email', $validated['gemini_email'] ?? '');

        // La clave solo se actualiza si el usuario escribió una nueva.
        if (filled($validated['api_key'] ?? null)) {
            Setting::put('ai.api_key', $validated['api_key']);
        }

        // La contraseña de Gemini solo se actualiza si se escribió una nueva.
        if (filled($validated['gemini_password'] ?? null)) {
            Setting::put('ai.gemini_password', $validated['gemini_password']);
        }

        return back()->with('success', 'Configuración de IA actualizada.');
    }
}
