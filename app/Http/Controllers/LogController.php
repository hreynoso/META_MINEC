<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ExportName;
use App\Support\SheetExport;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    private const SECTION = [
        'Project' => 'Proyectos',
        'Kpi' => 'KPIs',
        'Institution' => 'Instituciones',
        'PresidentialGoal' => 'Metas presidenciales',
        'User' => 'Usuarios',
        'Role' => 'Roles y permisos',
        'Message' => 'Red de Gestores',
        'Setting' => 'Configuración',
    ];

    private const EVENT = [
        'created' => 'Creación',
        'updated' => 'Actualización',
        'deleted' => 'Eliminación',
    ];

    public function index(): Response
    {
        $logs = Activity::with('causer')
            ->latest()
            ->limit(300)
            ->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id,
                'datetime' => $a->created_at?->format('d/m/Y h:i A'),
                'user' => $this->causerName($a),
                'action' => self::EVENT[$a->event] ?? ($a->description ?: '—'),
                'section' => $this->section($a),
                'detail' => $this->detail($a),
            ]);

        return Inertia::render('Logs/Index', ['logs' => $logs]);
    }

    public function export(): StreamedResponse
    {
        $rows = Activity::with('causer')->latest()->limit(2000)->get()
            ->map(fn (Activity $a) => [
                $a->created_at?->format('d/m/Y h:i A'),
                $this->causerName($a),
                self::EVENT[$a->event] ?? ($a->description ?: '—'),
                $this->section($a),
                $this->detail($a),
            ])->all();

        return SheetExport::stream(ExportName::make('Logs del Sistema', 'xlsx'), ['Fecha y hora', 'Usuario', 'Acción', 'Sección', 'Detalle'], $rows);
    }

    /** Cache de correo → nombre para no repetir consultas en listados largos. */
    private static array $nameByEmail = [];

    /**
     * Nombres y apellidos del actor. En eventos de acceso el causer suele venir
     * vacío (intentos fallidos, bloqueos), así que se resuelve por el correo
     * registrado en las propiedades del evento.
     */
    private function causerName(Activity $a): string
    {
        if (filled($a->causer?->name)) {
            return $a->causer->name;
        }

        $email = $a->properties->get('email');

        if (filled($email)) {
            if (! array_key_exists($email, self::$nameByEmail)) {
                self::$nameByEmail[$email] = User::where('email', $email)->value('name');
            }

            return self::$nameByEmail[$email] ?? $email;
        }

        return 'Sistema';
    }

    private function section(Activity $a): string
    {
        if ($a->log_name === 'auth') {
            return 'Accesos';
        }

        if (! $a->subject_type) {
            return 'Sistema';
        }

        $base = class_basename($a->subject_type);

        return self::SECTION[$base] ?? $base;
    }

    private function detail(Activity $a): string
    {
        // Eventos de acceso: correo + IP (nunca la contraseña).
        if ($a->log_name === 'auth') {
            $email = $a->properties->get('email');
            $ip = $a->properties->get('ip');

            return trim(implode(' · ', array_filter([
                $email,
                $ip ? 'IP '.$ip : null,
            ])), ' ·') ?: '—';
        }

        $attrs = $a->properties->get('attributes', []);

        if (! is_array($attrs) || $attrs === []) {
            return $a->description ?: '—';
        }

        $label = $attrs['name'] ?? $attrs['label'] ?? $attrs['code'] ?? null;
        $fields = implode(', ', array_slice(array_keys($attrs), 0, 6));

        return trim(($label ? '"'.$label.'" · ' : '').($fields ? 'campos: '.$fields : ''), ' ·');
    }
}
