<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración → Idioma. Define el idioma del sistema (inicialmente español e
 * inglés). Se aplica en el backend vía SetLocale y en el frontend vía vue-i18n.
 */
class LanguageSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Language', [
            'current' => Locale::current(),
            'options' => Locale::options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(Locale::SUPPORTED))],
        ]);

        Setting::put(Locale::SETTING_KEY, $data['locale']);

        // El mensaje sale ya en el idioma recién elegido.
        App::setLocale($data['locale']);

        return back()->with('success', __('config.language.saved'));
    }
}
