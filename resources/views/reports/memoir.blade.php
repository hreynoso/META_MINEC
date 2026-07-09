<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Memoria Institucional</title>
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { color: #1e293b; font-size: 11px; margin: 28px; }
        .logo-wrap { text-align: center; margin-bottom: 10px; }
        .logo { max-height: 72px; }
        .title-block { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .brand { color: #0d9488; font-weight: bold; font-size: 11px; letter-spacing: .5px; }
        h1 { font-size: 20px; margin: 6px 0 2px; }
        .entity { font-size: 14px; color: #334155; }
        .meta { color: #94a3b8; font-size: 10px; margin-top: 4px; }
        .content { font-size: 11px; }
        .content .section { font-size: 13px; font-weight: bold; color: #0d9488; margin: 16px 0 5px; }
        .content .section:first-child { margin-top: 0; }
        .content .para { text-align: justify; line-height: 1.55; margin: 0 0 9px; }
        .content .bullet { text-align: justify; line-height: 1.5; margin: 0 0 4px; padding-left: 14px; text-indent: -8px; }
        .content .bullet:before { content: "•  "; color: #0d9488; }
        .content strong { font-weight: bold; }
        .footer { margin-top: 20px; color: #94a3b8; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @if (!empty($logo))
        <div class="logo-wrap"><img src="{{ $logo }}" class="logo" alt="Logo institucional"></div>
    @endif

    <div class="title-block">
        <div class="brand">{{ $institution ?? 'MINEC' }} · Sistema META</div>
        <h1>Memoria Institucional</h1>
        <div class="entity">{{ $entity }}@if (!empty($entity_short)) ({{ $entity_short }})@endif</div>
        <div class="meta">Período {{ $periodo }} · Generado el {{ $generated_at }}</div>
    </div>

    <div class="content">{!! \App\Support\ReportFormat::toHtml($content) !!}</div>

    <div class="footer">Ministerio de Economía de El Salvador — Documento generado automáticamente por Sistema META.</div>
</body>
</html>
