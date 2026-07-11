<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 24px; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .header table { width: 100%; border-collapse: collapse; margin: 0; }
        .header td { border: 0; padding: 0; vertical-align: middle; }
        .logo { height: 48px; }
        .brand { color: #0d9488; font-weight: bold; font-size: 12px; letter-spacing: .5px; }
        h1 { font-size: 16px; margin: 4px 0 2px; }
        .subtitle { color: #64748b; font-size: 11px; }
        .meta { color: #94a3b8; font-size: 10px; margin-top: 2px; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 6px 0 12px; }
        .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; background: #f8fafc; }
        .card .k { color: #94a3b8; font-size: 9px; text-transform: uppercase; }
        .card .v { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .banner { padding: 8px 10px; border-radius: 6px; font-weight: bold; margin: 4px 0 12px; }
        .banner.ok { background: #ccfbf1; color: #0f766e; }
        .banner.bad { background: #fee2e2; color: #b91c1c; }
        .banner.na { background: #f1f5f9; color: #475569; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .sev { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; text-transform: capitalize; font-weight: bold; }
        .sev.critical, .sev.high { background: #fee2e2; color: #b91c1c; }
        .sev.medium { background: #fef3c7; color: #b45309; }
        .sev.low, .sev.none { background: #e2e8f0; color: #475569; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 10px; }
        .footer { margin-top: 16px; color: #94a3b8; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="brand">{{ $institution ?? 'MINEC' }} · Sistema META</div>
                    <h1>{{ $title }}</h1>
                    <div class="subtitle">{{ $subtitle }}</div>
                    <div class="meta">Generado el {{ $generated_at }} · ISO 27001 A.8.8</div>
                </td>
                @if (!empty($logo))
                    <td style="text-align: right; width: 180px;">
                        <img src="{{ $logo }}" class="logo" alt="Logo institucional">
                    </td>
                @endif
            </tr>
        </table>
    </div>

    <table class="cards">
        <tr>
            <td class="card" style="width: 25%;">
                <div class="k">Estado del análisis</div>
                <div class="v">{{ $available ? 'Ejecutado' : 'No disponible' }}</div>
            </td>
            <td class="card" style="width: 25%;">
                <div class="k">Vulnerabilidades</div>
                <div class="v">{{ $count }}</div>
            </td>
            <td class="card" style="width: 25%;">
                <div class="k">Último análisis</div>
                <div class="v" style="font-size: 11px;">{{ $ran_at ?? '—' }}</div>
            </td>
            <td class="card" style="width: 25%;">
                <div class="k">Periodicidad</div>
                <div class="v" style="font-size: 11px;">Cada {{ $interval_days }} días</div>
            </td>
        </tr>
    </table>

    @if (!$available)
        <div class="banner na">La herramienta de análisis (composer audit) no está disponible en el entorno de ejecución. El análisis autoritativo se ejecuta además en el pipeline de CI.</div>
    @elseif ($count === 0)
        <div class="banner ok">Sin vulnerabilidades conocidas en las dependencias.</div>
    @else
        <div class="banner bad">Se detectaron {{ $count }} vulnerabilidad(es) conocida(s) que requieren atención.</div>
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 28%;">Paquete</th>
                    <th style="width: 14%;">Severidad</th>
                    <th>Aviso</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($advisories as $a)
                    <tr>
                        <td class="mono">{{ $a['package'] ?? '' }}</td>
                        <td>
                            @php($sev = strtolower($a['severity'] ?? ''))
                            <span class="sev {{ $sev !== '' ? $sev : 'none' }}">{{ $sev !== '' ? $sev : '—' }}</span>
                        </td>
                        <td>
                            {{ $a['title'] ?? ($a['cve'] ?? '—') }}
                            @if (!empty($a['cve']) && !empty($a['title'])) <span class="mono" style="color:#94a3b8;">({{ $a['cve'] }})</span> @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">Ministerio de Economía de El Salvador — Documento generado automáticamente por Sistema META (control A.8.8, gestión de vulnerabilidades técnicas).</div>
</body>
</html>
