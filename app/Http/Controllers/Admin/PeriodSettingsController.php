<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\LocalTime;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Configuración → Gestión de Períodos. Define el año de ejecución (año en curso)
 * y el año de planificación (año próximo = ejecución + 1), con una fecha
 * configurable de activación para el período de planificación. La lógica de
 * lectura vive en App\Support\Period.
 */
class PeriodSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Periods', [
            'executionYear' => Period::executionYear(),
            'planningYear' => Period::planningYear(),
            'planningActivationDate' => Period::planningActivationDate(),
            'planningActive' => Period::planningIsActive(),
            'currentYear' => (int) Carbon::now(LocalTime::FALLBACK_TZ)->year,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'execution_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'planning_activation_date' => ['nullable', 'date'],
        ]);

        Setting::put(Period::EXECUTION_YEAR, (string) $data['execution_year']);

        Setting::put(
            Period::PLANNING_ACTIVATION,
            ! empty($data['planning_activation_date'])
                ? Carbon::parse($data['planning_activation_date'])->toDateString()
                : ''
        );

        return back()->with('success', __('messages.periods.updated'));
    }
}
