<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Volumen de Citas</title>
<style>
 body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
 h1 { font-size: 18px; margin:0 0 8px; }
 table { width:100%; border-collapse: collapse; margin-top:12px; }
 th, td { border:1px solid #ccc; padding:4px 6px; font-size:11px; }
 th { background:#f3f4f6; text-align:left; }
 .meta { font-size:11px; color:#555; margin-top:4px; }
 .footer { position:fixed; bottom:0; left:0; right:0; font-size:10px; text-align:center; color:#888; }
</style>
</head>
<body>
  <h1>Volumen de Citas</h1>
  <div class="meta">Rango: {{ $start }} a {{ $end }} | Generado: {{ $generated_at }}</div>
  <table>
    <thead>
      <tr>
        @php $first = (array)($rows[0] ?? []); @endphp
        @if(empty($first))
          <th>Periodo</th><th>Grupo</th><th>Total</th>
        @else
          @foreach(array_keys($first) as $col)
            <th>{{ $col }}</th>
          @endforeach
        @endif
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        @php $r = (array)$r; @endphp
        <tr>
          @if(empty($first))
            <td>{{ $r['period'] ?? '' }}</td>
            <td>{{ $r['group'] ?? '' }}</td>
            <td>{{ $r['total'] ?? 0 }}</td>
          @else
            @foreach(array_keys($first) as $col)
              <td>{{ $r[$col] ?? '' }}</td>
            @endforeach
          @endif
        </tr>
      @empty
        <tr><td colspan="3">Sin datos</td></tr>
      @endforelse
    </tbody>
  </table>
  <div class="footer">Reporte generado automáticamente - Sistema de Consultorio</div>
</body>
</html>
