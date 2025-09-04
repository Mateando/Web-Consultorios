<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
}
