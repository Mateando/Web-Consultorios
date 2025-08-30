<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $item->title }} - Plantilla</title>
    <style>
        body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; margin: 24px; color: #111827 }
        .header { border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 18px }
        .title { font-size: 20px; font-weight: 700; color: #0f172a }
        .description { white-space: pre-wrap; margin-top: 10px; line-height: 1.45 }
        .footer { margin-top: 36px; color: #6b7280; font-size: 13px }
        @media print { .no-print { display: none } }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $item->title }}</div>
    </div>

    <div class="description">{!! nl2br(e($item->description)) !!}</div>

    <div class="footer no-print">
        <p>Generado: {{ now()->toDateTimeString() }}</p>
        <p><button onclick="window.print()">Imprimir</button></p>
    </div>
</body>
</html>
