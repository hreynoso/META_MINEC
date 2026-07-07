<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 24px; }
        .header { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 14px; }
        .brand { color: #0d9488; font-weight: bold; font-size: 12px; letter-spacing: .5px; }
        h1 { font-size: 16px; margin: 4px 0 2px; }
        .subtitle { color: #64748b; font-size: 11px; }
        .meta { color: #94a3b8; font-size: 10px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #475569; border-bottom: 1px solid #cbd5e1; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 16px; color: #94a3b8; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">MINEC · Sistema META</div>
        <h1>{{ $title }}</h1>
        <div class="subtitle">{{ $subtitle }}</div>
        <div class="meta">Generado el {{ $generated_at }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $c)
                    <th>{{ $c }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center;color:#94a3b8;padding:20px;">Sin datos para este reporte.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Ministerio de Economía de El Salvador — Documento generado automáticamente por Sistema META.</div>
</body>
</html>
