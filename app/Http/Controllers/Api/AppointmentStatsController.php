<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Doctor;
use App\Models\Specialty;

class AppointmentStatsController extends Controller
{
    public function index(Request $request)
    {
        // Rango de fecha opcional (por defecto últimos 30 días)
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : Carbon::now()->endOfDay();

        // Base query
        $appointments = DB::table('appointments')->whereBetween('appointment_date', [$start, $end]);

        // 1) Volumen de citas: totales por día (últimos N días)
        $by_day = DB::table('appointments')
            ->select(DB::raw("DATE(appointment_date) as day"), DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // 1b) por profesional
        $by_professional = DB::table('appointments')
            ->select('doctor_id', DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('doctor_id')
            ->orderByDesc('total')
            ->limit(50)
            ->get();

        // 1c) por especialidad
        $by_specialty = DB::table('appointments')
            ->select('specialty_id', DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('specialty_id')
            ->orderByDesc('total')
            ->get();

        // 1d) por tipo de paciente (campo patient_type en patients)
        $by_patient_type = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->select('patients.patient_type', DB::raw('count(*) as total'))
            ->whereBetween('appointments.appointment_date', [$start, $end])
            ->groupBy('patients.patient_type')
            ->orderByDesc('total')
            ->get();

        // 1e) por motivo (reason)
        $by_reason = DB::table('appointments')
            ->select('reason', DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('reason')
            ->orderByDesc('total')
            ->get();

        // canceladas
        $canceled = DB::table('appointments')
            ->whereBetween('appointment_date', [$start, $end])
            ->where('status', 'cancelada')
            ->count();

        // reprogramadas: buscar en appointment_audits cambios en appointment_date
        $reprogrammed = DB::table('appointment_audits')
            ->whereBetween('created_at', [$start, $end])
            ->where('action', 'updated')
            ->whereRaw("changes like '%appointment_date%'")
            ->count();

        // Productividad: promedio de citas completadas por día por profesional (últimos 30 días)
        $completed_by_doc = DB::table('appointments')
            ->select('doctor_id', DB::raw('count(*) as completed'))
            ->whereBetween('appointment_date', [$start, $end])
            ->where('status', 'completada')
            ->groupBy('doctor_id')
            ->orderByDesc('completed')
            ->get();

        $days = max(1, $end->diffInDays($start));
        $avg_per_doc = $completed_by_doc->map(function ($r) use ($days) {
            return [
                'doctor_id' => $r->doctor_id,
                'avg_per_day' => round($r->completed / $days, 2),
                'completed' => $r->completed,
            ];
        });

        // Tiempo promedio por consulta (usar duration)
        $avg_duration = DB::table('appointments')
            ->whereBetween('appointment_date', [$start, $end])
            ->whereNotNull('duration')
            ->avg('duration');

        // asistencia vs ausentismo
        $attended = DB::table('appointments')->whereBetween('appointment_date', [$start, $end])->where('status', 'completada')->count();
        $no_show = DB::table('appointments')->whereBetween('appointment_date', [$start, $end])->where('status', 'no_asistio')->count();

        // Horarios más demandados: agrupar por hora
        $by_hour = DB::table('appointments')
            ->select(DB::raw('HOUR(appointment_date) as hour'), DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('hour')
            ->orderByDesc('total')
            ->get();

        // Dias pico: agrupar por weekday
        $by_weekday = DB::table('appointments')
            ->select(DB::raw('DAYOFWEEK(appointment_date) as weekday'), DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('weekday')
            ->orderByDesc('total')
            ->get();

        // Demografía: por edad y género (approx)
        $by_gender = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->join('users', 'patients.user_id', '=', 'users.id')
            ->select('users.gender', DB::raw('count(*) as total'))
            ->whereBetween('appointments.appointment_date', [$start, $end])
            ->groupBy('users.gender')
            ->get();

        // Obras sociales más utilizadas
        $by_insurance = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->select('patients.insurance_provider', DB::raw('count(*) as total'))
            ->whereBetween('appointments.appointment_date', [$start, $end])
            ->groupBy('patients.insurance_provider')
            ->orderByDesc('total')
            ->get();

        // Indicadores administrativos: citas por creador
        $by_creator = DB::table('appointments')
            ->select('created_by', DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('created_by')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'range' => [ 'start' => $start->toDateString(), 'end' => $end->toDateString() ],
            'volumen' => [
                'by_day' => $by_day,
                'by_professional' => $by_professional,
                'by_specialty' => $by_specialty,
                'by_patient_type' => $by_patient_type,
                'by_reason' => $by_reason,
                'canceled' => $canceled,
                'reprogrammed' => $reprogrammed,
            ],
            'productividad' => [
                'completed_by_doc' => $avg_per_doc,
                'avg_duration_minutes' => $avg_duration ? round($avg_duration,2) : null,
                'attended' => $attended,
                'no_show' => $no_show,
            ],
            'uso' => [
                'by_hour' => $by_hour,
                'by_weekday' => $by_weekday,
            ],
            'demografia' => [
                'by_gender' => $by_gender,
                'by_insurance' => $by_insurance,
            ],
            'admin' => [
                'by_creator' => $by_creator,
            ],
        ]);
    }

    /**
     * Volumen de citas: endpoint especializado que devuelve series agrupadas
     * params: start, end, group=day|week|month, by=doctor|specialty|reason
     */
    public function volume(Request $request)
    {
        $start = $request->query('start') ? Carbon::parse($request->query('start')) : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->query('end') ? Carbon::parse($request->query('end')) : Carbon::now()->endOfDay();
        $group = $request->query('group', 'day');
        $by = $request->query('by', null); // doctor, specialty, reason

        $format = $request->query('format', null); // 'series' to return labels+datasets

        // Apply role filter: if user is doctor, limit to their doctor_id
        $user = Auth::user();
        $doctorScope = null;
        if ($user) {
            // Try find doctor record
            $doc = Doctor::where('user_id', $user->id)->first();
            if ($doc) $doctorScope = $doc->id;
        }

        // For now produce simple day-based periods
        if ($group !== 'day') {
            // fallback to day for series pivoting
            $group = 'day';
        }

        // Build list of periods (dates) between start and end
        $periods = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $periods[] = $cursor->toDateString();
            $cursor->addDay();
        }

        if ($format === 'series') {
            if ($by === 'doctor') {
                $rows = DB::table('appointments')
                    ->select(DB::raw('DATE(appointment_date) as period'), 'doctor_id', DB::raw('count(*) as total'))
                    ->whereBetween('appointment_date', [$start, $end])
                    ->when($doctorScope, fn($q) => $q->where('doctor_id', $doctorScope))
                    ->groupBy('period','doctor_id')
                    ->get();

                $doctorIds = $rows->pluck('doctor_id')->unique()->values()->all();
                $doctors = Doctor::whereIn('id', $doctorIds)->get()->keyBy('id');

                $datasets = [];
                foreach ($doctorIds as $did) {
                    $label = $doctors[$did]->user->name ?? ('Dr. '.$did);
                    $map = $rows->where('doctor_id', $did)->keyBy('period');
                    $data = [];
                    foreach ($periods as $p) {
                        $data[] = isset($map[$p]) ? (int)$map[$p]->total : 0;
                    }
                    $datasets[] = ['label' => $label, 'data' => $data];
                }

                return response()->json(['start' => $start->toDateString(), 'end' => $end->toDateString(), 'labels' => $periods, 'datasets' => $datasets]);
            }

            if ($by === 'specialty') {
                $rows = DB::table('appointments')
                    ->select(DB::raw('DATE(appointment_date) as period'), 'specialty_id', DB::raw('count(*) as total'))
                    ->whereBetween('appointment_date', [$start, $end])
                    ->when($doctorScope, fn($q) => $q->where('doctor_id', $doctorScope))
                    ->groupBy('period','specialty_id')
                    ->get();

                $specIds = $rows->pluck('specialty_id')->unique()->values()->all();
                $specs = Specialty::whereIn('id', $specIds)->get()->keyBy('id');

                $datasets = [];
                foreach ($specIds as $sid) {
                    $label = $specs[$sid]->name ?? ('Spec '.$sid);
                    $map = $rows->where('specialty_id', $sid)->keyBy('period');
                    $data = [];
                    foreach ($periods as $p) {
                        $data[] = isset($map[$p]) ? (int)$map[$p]->total : 0;
                    }
                    $datasets[] = ['label' => $label, 'data' => $data];
                }

                return response()->json(['start' => $start->toDateString(), 'end' => $end->toDateString(), 'labels' => $periods, 'datasets' => $datasets]);
            }

            // default: totals per day
            $rows = DB::table('appointments')
                ->select(DB::raw('DATE(appointment_date) as period'), DB::raw('count(*) as total'))
                ->whereBetween('appointment_date', [$start, $end])
                ->when($doctorScope, fn($q) => $q->where('doctor_id', $doctorScope))
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $map = $rows->keyBy('period');
            $data = [];
            foreach ($periods as $p) {
                $data[] = isset($map[$p]) ? (int)$map[$p]->total : 0;
            }

            return response()->json(['start' => $start->toDateString(), 'end' => $end->toDateString(), 'labels' => $periods, 'datasets' => [['label' => 'Citas','data' => $data]]]);
        }

        // Fallback: original behavior (raw rows)
        $by_day = DB::table('appointments')
            ->select(DB::raw("DATE(appointment_date) as day"), DB::raw('count(*) as total'))
            ->whereBetween('appointment_date', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json(['start' => $start->toDateString(), 'end' => $end->toDateString(), 'volumen' => ['by_day' => $by_day]]);
    }

    /**
     * Export CSV simple para el volumen: reutiliza volume() y retorna CSV
     */
    public function exportCsv(Request $request)
    {
        $res = $this->volume($request)->getData();
        $rows = $res->data;

        $filename = 'appointments_volume_'.now()->format('Ymd_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}" ,
        ];

        $callback = function() use ($rows) {
            $out = fopen('php://output', 'w');
            // header
            fputcsv($out, array_keys((array)$rows[0] ?? ['period','group','total']));
            foreach ($rows as $r) {
                fputcsv($out, (array)$r);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
