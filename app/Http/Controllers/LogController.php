<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

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
                'user' => $a->causer?->name ?? 'Sistema',
                'action' => self::EVENT[$a->event] ?? ($a->description ?: '—'),
                'section' => $this->section($a->subject_type),
                'detail' => $this->detail($a),
            ]);

        return Inertia::render('Logs/Index', ['logs' => $logs]);
    }

    private function section(?string $type): string
    {
        if (! $type) {
            return 'Sistema';
        }

        $base = class_basename($type);

        return self::SECTION[$base] ?? $base;
    }

    private function detail(Activity $a): string
    {
        $attrs = $a->properties->get('attributes', []);

        if (! is_array($attrs) || $attrs === []) {
            return $a->description ?: '—';
        }

        $label = $attrs['name'] ?? $attrs['label'] ?? $attrs['code'] ?? null;
        $fields = implode(', ', array_slice(array_keys($attrs), 0, 6));

        return trim(($label ? '"'.$label.'" · ' : '').($fields ? 'campos: '.$fields : ''), ' ·');
    }
}
