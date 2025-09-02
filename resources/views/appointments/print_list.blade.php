<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Listado de Turnos</title>
    <style>
        /* A4 print settings */
        @page { size: A4; margin: 18mm; }

        html, body { font-family: Arial, Helvetica, sans-serif; color: #111; }
        .printable-area { max-width: 210mm; margin: 0 auto; }
        .header { display:flex; align-items:center; gap:16px; margin-bottom:18px; }
        .logo { height:60px; width:auto; object-fit:contain; }
        .clinic-title { margin:0; font-size:18px; }
        .clinic-meta { font-size:12px; color:#555; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #ddd; padding:10px; font-size:12px; vertical-align:top; }
        th { background:#f7f7f7; text-align:left; }
        .small { font-size:11px; color:#666; }

        /* Print helpers */
        @media print {
            .no-print { display:none !important; }
            .print-only { display:block !important; }
            img { max-width: 100%; height: auto; }
        }

        /* Button style similar to app buttons */
        .btn-primary {
            display:inline-flex; align-items:center; gap:8px; border-radius:6px; padding:8px 12px; background:#2563eb; color:#fff; text-decoration:none; border:1px solid rgba(0,0,0,0.05);
            font-size:14px; cursor:pointer;
        }
        .btn-primary:hover { background:#1e40af; }

        .status { padding:4px 8px; border-radius:12px; color:#fff; display:inline-block; font-size:12px }
        .status-programada { background:#3B82F6 }
        .status-confirmada { background:#10B981 }
        .status-completada { background:#6B7280 }
        .status-cancelada { background:#EF4444 }
        .status-no_asistio { background:#9CA3AF }
    </style>
</head>
<body>
    <div class="printable-area">
        <div class="no-print" style="text-align:right;margin-bottom:12px">
            <button class="btn-primary" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 13H4a2 2 0 00-2 2v4a2 2 0 002 2h16a2 2 0 002-2v-4a2 2 0 00-2-2h-2M6 13h12v8H6v-8z"/></svg>
                Imprimir
            </button>
        </div>

        @php
            // Detectar si $appointments es un paginator o una colección simple
            $isPaginator = isset($appointments) && $appointments instanceof \Illuminate\Pagination\LengthAwarePaginator;
            if ($isPaginator) {
                $total = $appointments->total();
            } else {
                $total = isset($appointments) && is_countable($appointments) ? count($appointments) : 0;
            }
            $isLarge = request()->query('large') === '1' || ($total > 500);
        @endphp

        <div class="header">
            @if(!empty($clinicInfo['logo']))
                <img src="{{ $clinicInfo['logo'] }}" alt="Logo" class="logo">
            @endif
            <div>
                <h2 class="clinic-title">{{ $clinicInfo['name'] ?? 'Consultorio' }} <span style="font-weight:normal; font-size:13px; color:#666">- Listado de Turnos</span></h2>
                <div class="clinic-meta">
                    @if(!empty($clinicInfo['address'])){{ $clinicInfo['address'] }}@endif
                    @if(!empty($clinicInfo['phone'])) &middot; Tel: {{ $clinicInfo['phone'] }}@endif
                    @if(!empty($clinicInfo['email'])) &middot; {{ $clinicInfo['email'] }}@endif
                </div>
                <div class="small">Generado por: {{ $generated_by }} el {{ $generated_at->format('d/m/Y H:i') }} &nbsp; | &nbsp; Total: {{ $total }}</div>

                @if(!empty($filterLabels))
                    <div class="small">Filtros aplicados:
                        @foreach($filterLabels as $k => $v)
                            @if($v)
                                <strong>{{ ucfirst(str_replace('_',' ', $k)) }}:</strong> {{ $v }}&nbsp;
                            @endif
                        @endforeach
                    </div>
                @endif
                @php
                    $isEmpty = $total === 0;
                @endphp
                @if($isEmpty)
                    <div style="margin-top:8px; padding:8px; background:#fff3cd; border:1px solid #ffecb5; color:#856404; border-radius:6px; font-size:13px;">No se encontraron citas para los filtros seleccionados.</div>
                @elseif($isLarge)
                    <div style="margin-top:8px; padding:8px; background:#fffbe6; border:1px solid #fff1b8; color:#7a5900; border-radius:6px; font-size:13px;">Advertencia: la lista contiene muchas citas ({{ $total }}). La impresión puede tardar o generar varias páginas.</div>
                @endif
            </div>
            </div>

        @if($isPaginator)
            <div style="margin-top:12px; font-size:13px; color:#444">Mostrando página {{ $appointments->currentPage() }} de {{ $appointments->lastPage() }}. La impresión realizará la página actual.</div>
        @else
            <div style="margin-top:12px; font-size:13px; color:#444">Mostrando todos los registros ({{ $total }}). La impresión incluirá todo el listado.</div>
        @endif

        <table>
        <thead>
            <tr>
                <th>Fecha y Hora</th>
                <th>Paciente</th>
                <th>Doctor</th>
                <th>Especialidad</th>
                <th>Estado</th>
                <th>Motivo / Notas</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appointments as $a)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->appointment_date)->format('d/m/Y H:i') }}</td>
                    <td>{{ $a->patient->user->name }}<br><span class="small">{{ $a->patient->user->document_type }} {{ $a->patient->user->document_number }}</span></td>
                    <td>{{ $a->doctor->user->name }}</td>
                    <td>{{ $a->specialty->name ?? 'General' }}</td>
                    <td>
                        <span class="status status-{{ $a->status }}">{{ ucfirst($a->status) }}</span>
                    </td>
                    <td>{{ $a->reason }}<br><span class="small">{{ $a->notes }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @if($isPaginator)
        <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center">
            <div class="small no-print">
                @if($appointments->previousPageUrl())
                    <a href="{{ $appointments->previousPageUrl() }}" class="btn-primary" style="background:#e5e7eb;color:#111;padding:6px 10px;border-radius:6px;text-decoration:none">&larr; Anterior</a>
                @endif
                @if($appointments->nextPageUrl())
                    <a href="{{ $appointments->nextPageUrl() }}" class="btn-primary" style="background:#e5e7eb;color:#111;padding:6px 10px;border-radius:6px;text-decoration:none;margin-left:8px">Siguiente &rarr;</a>
                @endif
            </div>
            <div class="small no-print">Página {{ $appointments->currentPage() }} / {{ $appointments->lastPage() }}</div>
        </div>
    @endif
</body>
</html>
