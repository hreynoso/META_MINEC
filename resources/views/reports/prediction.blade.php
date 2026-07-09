<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Riesgo — {{ $p['name'] }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 28px; }
        .logo-wrap { text-align: center; margin-bottom: 10px; }
        .logo { max-height: 72px; }
        .title-block { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #0d9488; font-weight: bold; font-size: 11px; letter-spacing: .5px; }
        h1 { font-size: 20px; margin: 6px 0 2px; }
        .project-name { font-size: 14px; color: #334155; }
        .meta { color: #94a3b8; font-size: 10px; margin-top: 4px; }
        .section-title { color: #0d9488; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; margin: 16px 0 6px; font-weight: bold; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.grid td { border: 1px solid #e2e8f0; padding: 8px 10px; width: 25%; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .value { font-size: 14px; font-weight: bold; }
        .value.small { font-size: 11px; }
        ul.factors { margin: 0; padding-left: 16px; }
        ul.factors li { margin-bottom: 3px; }
        table.trace { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.trace th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        table.trace td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.trace tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 20px; color: #94a3b8; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @if (!empty($logo))
        <div class="logo-wrap"><img src="{{ $logo }}" class="logo" alt="Logo institucional"></div>
    @endif

    <div class="title-block">
        <div class="brand">{{ $institution ?? 'MINEC' }} · Sistema META — IA Predictiva</div>
        <h1>Riesgo: {{ $p['risk_label'] }}</h1>
        <div class="project-name">{{ $p['name'] }}</div>
        <div class="meta">{{ $p['code'] }}@if (!empty($p['institution'])) · {{ $p['institution'] }}@endif · Generado el {{ $generated_at }}</div>
    </div>

    <div class="section-title">Evaluación del modelo META-PREDICT</div>
    <table class="grid">
        <tr>
            <td><span class="label">Probabilidad de éxito</span><span class="value">{{ $p['score'] }}%</span></td>
            <td><span class="label">Nivel de riesgo</span><span class="value">{{ $p['risk_label'] }}</span></td>
            <td><span class="label">Avance físico</span><span class="value">{{ $p['physical_progress'] }}%</span></td>
            <td><span class="label">Ejecución financiera</span><span class="value">{{ $p['financial_progress'] }}%</span></td>
        </tr>
        <tr>
            <td><span class="label">Estado</span><span class="value small">{{ $p['status_label'] }}</span></td>
            <td><span class="label">Responsable</span><span class="value small">{{ $p['responsible'] ?? '—' }}</span></td>
            <td colspan="2"><span class="label">Diagnóstico</span><span class="value small">{{ $p['failing'] ? 'En riesgo de fracaso' : 'En trayectoria aceptable' }}</span></td>
        </tr>
    </table>

    <div class="section-title">Factores considerados</div>
    <ul class="factors">
        @foreach ($p['factors'] as $f)
            <li>{{ $f }}</li>
        @endforeach
    </ul>

    <div class="section-title">Trazabilidad de recomendaciones (IA)</div>
    <table class="trace">
        <thead>
            <tr>
                <th style="width: 120px;">Fecha y hora</th>
                <th style="width: 150px;">Usuario</th>
                <th>Recomendación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $h)
                <tr>
                    <td>{{ $h['datetime'] }}</td>
                    <td>{{ $h['user'] }}</td>
                    <td>{{ $h['recommendation'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#64748b; padding:14px;">
                        Aún no se han generado recomendaciones con IA para este proyecto.<br>
                        Recomendación del modelo: {{ $p['recommendation'] }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Ministerio de Economía de El Salvador — Documento generado automáticamente por Sistema META · IA Predictiva.</div>
</body>
</html>
