<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Ai\AiReportService;
use Illuminate\Http\JsonResponse;
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
        ], [], [
            'provider' => 'proveedor',
            'model' => 'modelo',
            'api_key' => 'clave del API',
        ]);

        Setting::put('ai.provider', $validated['provider']);
        Setting::put('ai.model', $validated['model'] ?? '');
        Setting::put('ai.enabled', $request->boolean('enabled') ? '1' : '');

        // La clave solo se actualiza si el usuario escribió una nueva.
        if (filled($validated['api_key'] ?? null)) {
            Setting::put('ai.api_key', $validated['api_key']);
        }

        \App\Support\SecurityAlert::configChanged('Inteligencia Artificial (proveedor / clave del API)');

        return back()->with('success', __('messages.ai.updated'));
    }

    /** Prueba la conexión con el proveedor (usa la clave escrita o la guardada). */
    public function test(Request $request, AiReportService $ai): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:anthropic,gemini,openai'],
            'model' => ['nullable', 'string', 'max:100'],
            'api_key' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json(
            $ai->testConnection($data['provider'], $data['model'] ?? null, $data['api_key'] ?? null)
        );
    }

    /** Detecta los modelos que la clave del proveedor puede usar. */
    public function models(Request $request, AiReportService $ai): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'in:anthropic,gemini,openai'],
            'api_key' => ['nullable', 'string', 'max:255'],
        ]);

        return response()->json($ai->listModels($data['provider'], $data['api_key'] ?? null));
    }
}
