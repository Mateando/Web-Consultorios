<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir Cita</title>
    <style>
        /* A4 print setup */
        @page { size: A4; margin: 20mm; }
        html, body { height: 100%; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin:0; padding:0; }
        .print-container { box-sizing: border-box; padding: 8mm; }
        .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px }
        .clinic { text-align:left }
        .logo { max-height:80px; max-width:150px }
        .panel { border:1px solid #e5e7eb; padding:12px; border-radius:6px }
        table { width:100%; border-collapse:collapse; font-size:13px }
        td, th { padding:8px 6px; vertical-align:top }
        .label { color:#6b7280; font-size:12px }

        /* Primary button style (similar to PrimaryButton component) */
        .btn-primary {
            display:inline-flex; align-items:center; gap:8px;
            background-color:#1f6feb; color:#fff; border:none; padding:8px 12px; border-radius:6px;
            font-weight:600; cursor:pointer; box-shadow:0 1px 0 rgba(0,0,0,0.04);
        }
        .btn-primary:active { transform:translateY(1px) }

        /* Hide controls when printing */
        @media print {
            .no-print { display:none !important }
            a { color: #111; text-decoration: none }
            .panel { border: none }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="clinic">
            @if($clinicInfo['logo'])
                <img src="{{ $clinicInfo['logo'] }}" class="logo" alt="Logo">
            @endif
            <div><strong>{{ $clinicInfo['name'] ?? '' }}</strong></div>
            <div class="label">{{ $clinicInfo['address'] ?? '' }}</div>
            <div class="label">{{ $clinicInfo['phone'] ?? '' }} {{ $clinicInfo['email'] ? ' | ' . $clinicInfo['email'] : '' }}</div>
        </div>
        <div style="text-align:right">
            <div class="label">Generado por</div>
            <div>{{ Auth::user()->name ?? 'Sistema' }}</div>
            <div class="label">{{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="panel print-container">
        <h2>Detalle de la cita</h2>
        <table>
            <tr>
                <th class="label">Paciente</th>
                <td>{{ $appointment->patient->user->name ?? '-' }}</td>
                <th class="label">Doctor</th>
                <td>{{ $appointment->doctor->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <th class="label">Especialidad</th>
                <td>{{ $appointment->specialty->name ?? 'General' }}</td>
                <th class="label">Fecha y hora</th>
                <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th class="label">Estado</th>
                <td>{{ ucfirst($appointment->status) }}</td>
                <th class="label">Duración</th>
                <td>{{ $appointment->duration ?? 30 }} minutos</td>
            </tr>
            <tr>
                <th class="label">Motivo</th>
                <td colspan="3">{{ $appointment->reason ?? '-' }}</td>
            </tr>
            @if($appointment->notes)
            <tr>
                <th class="label">Notas</th>
                <td colspan="3" style="white-space:pre-wrap">{{ $appointment->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div style="margin-top:16px; text-align:right" class="no-print">
        <button class="btn-primary" onclick="window.print()" title="Imprimir" aria-label="Imprimir">
            <!-- simple print icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9V3h12v6M6 14h12v7H6v-7z" />
            </svg>
        </button>
    </div>
</body>
</html>
