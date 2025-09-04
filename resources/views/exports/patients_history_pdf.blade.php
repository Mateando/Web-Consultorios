<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Clínico</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111; }
        h1 { font-size:18px; margin:0 0 10px; }
        table { width:100%; border-collapse: collapse; margin-top:10px; }
        th, td { border:1px solid #ccc; padding:4px 6px; vertical-align: top; }
        th { background:#f3f4f6; font-weight:600; }
        .muted { color:#555; }
        .small { font-size:10px; }
    </style>
</head>
<body>
    @if($clinic)
        <table style="width:100%; margin-bottom:8px; border:0">
            <tr>
                <td style="width:70%; border:0; padding:0;">
                    <h1 style="margin:0 0 4px">{{ $clinic->name ?? 'Historial Clínico' }}</h1>
                    <div class="small muted">
                        {{ $clinic->address }}
                        @if($clinic->phone) • Tel: {{ $clinic->phone }} @endif
                        @if($clinic->email) • {{ $clinic->email }} @endif
                        @if($clinic->tax_id) • CUIT/NIF: {{ $clinic->tax_id }} @endif
                    </div>
                </td>
                <td style="text-align:right; border:0; padding:0;">
                    @if($clinic->logo_path)
                        <img src="{{ storage_path('app/public/'.$clinic->logo_path) }}" alt="Logo" style="max-height:70px; max-width:160px;" />
                    @endif
                </td>
            </tr>
        </table>
    @else
        <h1>Historial Clínico</h1>
    @endif
    <div class="small muted">Generado: {{ $generated_at->format('Y-m-d H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Doctor</th>
                <th>Especialidad</th>
                <th>Motivo</th>
                <th>Síntomas</th>
                <th>Diagnóstico</th>
                <th>Tratamiento</th>
                <th>Prescripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $a)
                <tr>
                    <td>{{ optional($a->appointment_date)->format('Y-m-d H:i') }}</td>
                    <td>cita</td>
                    <td>{{ $a->status }}</td>
                    <td>{{ optional(optional($a->doctor)->user)->name }}</td>
                    <td>{{ optional($a->specialty)->name }}</td>
                    <td>{{ $a->reason }}</td>
                    <td>{{ $a->symptoms }}</td>
                    <td>{{ $a->diagnosis }}</td>
                    <td>{{ $a->treatment }}</td>
                    <td>{{ $a->prescription }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($clinic && $clinic->footer_notes)
        <div style="margin-top:18px; font-size:10px; color:#444; white-space:pre-line;">{{ $clinic->footer_notes }}</div>
    @endif
</body>
</html>
