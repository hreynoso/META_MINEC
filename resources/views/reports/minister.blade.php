<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe presidencial</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 28px; }
        .logo-wrap { text-align: center; margin-bottom: 10px; }
        .logo { max-height: 72px; }
        .title-block { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #0d9488; font-weight: bold; font-size: 11px; letter-spacing: .5px; }
        h1 { font-size: 20px; margin: 6px 0 2px; }
        .meta { color: #94a3b8; font-size: 10px; margin-top: 4px; }
        .section-title { color: #0d9488; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; margin: 16px 0 6px; font-weight: bold; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        table.grid td { border: 1px solid #e2e8f0; padding: 8px 10px; width: 25%; }
        .label { color: #64748b; font-size: 9px; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .value { font-size: 14px; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
        .green { background: #10b981; } .amber { background: #f59e0b; } .red { background: #ef4444; }
        ul.recs { margin: 0; padding-left: 16px; }
        ul.recs li { margin-bottom: 5px; }
        .narrative { white-space: pre-wrap; line-height: 1.5; font-size: 11px; margin-top: 6px; }
        .footer { margin-top: 20px; color: #94a3b8; font-size: 9px; text-align: center; }
        .muted { color: #94a3b8; }
    </style>
</head>
<body>
    @if (!empty($logo))
        <div class="logo-wrap"><img src="{{ $logo }}" class="logo" alt="Logo institucional"></div>
    @endif

    <div class="title-block">
        <div class="brand">{{ $institution ?? 'MINEC' }} · Sistema META</div>
        <h1>Informe presidencial</h1>
        <div class="meta">
            Despacho de la Ministra de Economía · Período {{ $from }} al {{ $to }}<br>
            @if (!empty($selected))Instituciones: {{ implode(', ', $selected) }} · @endif Generado el {{ $generated_at }}
        </div>
    </div>

    <div class="section-title">Resumen ejecutivo</div>
    <table class="grid">
        <tr>
            <td><span class="label">Presupuesto total</span><span class="value">${{ number_format($summary['budget'], 0) }}</span></td>
            <td><span class="label">Ejecución agregada</span><span class="value">{{ $summary['executed_pct'] }}%</span></td>
            <td><span class="label">Beneficiarios directos</span><span class="value">{{ number_format($summary['beneficiaries']) }}</span></td>
            <td><span class="label">Proyectos críticos</span><span class="value">{{ $summary['critical'] }}</span></td>
        </tr>
        <tr>
            <td><span class="label">Proyectos monitoreados</span><span class="value">{{ $summary['projects_count'] }}</span></td>
            <td><span class="label">Instituciones</span><span class="value">{{ $summary['institutions_count'] }}</span></td>
            <td colspan="2"><span class="label">Ejecutado</span><span class="value">${{ number_format($summary['executed'], 0) }}</span></td>
        </tr>
    </table>

    <div class="section-title">KPIs estratégicos</div>
    <table class="data">
        <thead><tr><th>Indicador</th><th>Valor</th><th>Meta</th><th>Logro</th></tr></thead>
        <tbody>
            @forelse ($kpis as $k)
                <tr>
                    <td>{{ $k['label'] }}</td>
                    <td>{{ number_format((float) $k['value'], 2) }} {{ $k['unit'] }}</td>
                    <td>{{ number_format((float) $k['target'], 2) }}</td>
                    <td>{{ $k['achievement'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="text-align:center;">Sin indicadores.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Semáforo por institución</div>
    <table class="data">
        <thead><tr><th>Institución</th><th>Óptimos</th><th>Observación</th><th>Críticos</th><th>Estado</th></tr></thead>
        <tbody>
            @forelse ($byInstitution as $i)
                <tr>
                    <td>{{ $i['short_name'] }} — {{ $i['name'] }}</td>
                    <td>{{ $i['green'] }}</td>
                    <td>{{ $i['amber'] }}</td>
                    <td>{{ $i['red'] }}</td>
                    <td>
                        <span class="dot {{ $i['status'] === 'critico' ? 'red' : ($i['status'] === 'observacion' ? 'amber' : 'green') }}"></span>
                        {{ $i['status'] === 'critico' ? 'Crítico' : ($i['status'] === 'observacion' ? 'En observación' : 'Óptimo') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center;">Sin instituciones con proyectos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Alertas predictivas de IA</div>
    <table class="data">
        <thead><tr><th>Proyecto</th><th>Institución</th><th>Avance físico</th><th>Riesgo</th><th>Prob. éxito</th></tr></thead>
        <tbody>
            @forelse ($alerts as $a)
                <tr>
                    <td>{{ $a['name'] }}</td>
                    <td>{{ $a['institution'] }}</td>
                    <td>{{ $a['physical_progress'] }}%</td>
                    <td>{{ $a['risk'] }}</td>
                    <td>{{ $a['success'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center;">Sin proyectos en riesgo de fracaso.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Recomendaciones de acción</div>
    @if (count($recommendations))
        <ul class="recs">
            @foreach ($recommendations as $r)
                <li><strong>{{ $r['title'] }}</strong> (Prioridad {{ $r['priority'] }}) — {{ $r['detail'] }}</li>
            @endforeach
        </ul>
    @else
        <p class="muted">No hay intervenciones prioritarias por ahora.</p>
    @endif

    <div class="section-title">Informe ejecutivo (generado con IA)</div>
    <div class="narrative">{{ $narrative ?: 'Sin contenido generado por IA.' }}</div>

    <div class="footer">Ministerio de Economía de El Salvador — Documento generado automáticamente por Sistema META · Despacho de la Ministra.</div>
</body>
</html>
