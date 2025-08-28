<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DoctorScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Solo admin y doctores pueden gestionar horarios
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para ver esta página');
        }

        $search = trim($request->get('search', ''));
        $filterDoctorId = $request->get('doctor_id', '');

        // Si es doctor, solo puede ver/editar sus propios horarios
        if ($user->hasRole('medico')) {
            $doctor = $user->doctor;
            $doctors = collect([$doctor]);

            $query = $doctor->schedules()->with('specialty')->orderBy('day_of_week');

            // Aplicar filtros locales si existen
            if ($search !== '') {
                // Search by doctor name makes less sense for a single doctor, but we keep behavior for consistency
                // Only include schedules when doctor's name matches search
                if (stripos($doctor->user->name, $search) === false) {
                    $query->whereRaw('0 = 1'); // return empty
                }
            }
            if ($filterDoctorId) {
                // if doctor tries to filter to another doctor, return empty
                if ($filterDoctorId != $doctor->id) {
                    $query->whereRaw('0 = 1');
                }
            }

            $schedules = $query->get();
        } else {
            // Admin puede ver todos los doctores
            $doctors = Doctor::with(['user', 'schedules', 'specialties'])->get();

            $query = DoctorSchedule::query()
                ->select('doctor_schedules.*')
                ->with(['doctor.user', 'specialty'])
                ->leftJoin('doctors', 'doctors.id', '=', 'doctor_schedules.doctor_id')
                ->leftJoin('users', 'users.id', '=', 'doctors.user_id')
                ->orderByRaw('LOWER(users.name)')
                ->orderBy('doctor_schedules.day_of_week');

            if ($search !== '') {
                $query->where('users.name', 'like', "%{$search}%");
            }
            if ($filterDoctorId) {
                $query->where('doctors.id', $filterDoctorId);
            }

            $schedules = $query->paginate(6)->withQueryString();
        }
        
        // Obtener todas las especialidades activas
        $specialties = Specialty::where('is_active', true)->get();
        
        return Inertia::render('DoctorSchedules/Index', [
            'schedules' => $schedules,
            'doctors' => $doctors,
            'specialties' => $specialties,
            'filters' => ['search' => $search, 'doctor_id' => $filterDoctorId],
            'days_of_week' => [
                'monday' => 'Lunes',
                'tuesday' => 'Martes',
                'wednesday' => 'Miércoles',
                'thursday' => 'Jueves',
                'friday' => 'Viernes',
                'saturday' => 'Sábado',
                'sunday' => 'Domingo',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }
        
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'required|exists:specialties,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'appointment_duration' => 'required|integer|min:15|max:120',
        ]);
        
        // Si es doctor, verificar que solo edite sus propios horarios
        if ($user->hasRole('medico') && $user->doctor->id != $request->doctor_id) {
            abort(403, 'No puedes editar horarios de otros doctores');
        }
        
        // Validar que el doctor exista y tenga la especialidad seleccionada
        $doctor = Doctor::with('specialties')->find($request->doctor_id);
    // Nota: contains($id) en una colección de modelos no verifica por clave primaria, hay que usar contains('id', $id)
    if (!$doctor || !$doctor->specialties || !$doctor->specialties->contains('id', $request->specialty_id)) {
            return redirect()->back()->withErrors(['specialty_id' => 'El doctor no tiene asignada esta especialidad o no existe.']);
        }
        
        // Verificar conflictos de horario
        $hasConflict = DoctorSchedule::hasScheduleConflict(
            $request->doctor_id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time
        );
        
        if ($hasConflict) {
            $conflictingSchedules = DoctorSchedule::getConflictingSchedules(
                $request->doctor_id,
                $request->day_of_week,
                $request->start_time,
                $request->end_time
            );

            $conflicts = $conflictingSchedules->map(function($schedule) {
                $specName = optional($schedule->specialty)->name ?? 'Sin especialidad';
                return $specName . ' (' . $schedule->start_time . ' - ' . $schedule->end_time . ')';
            })->implode(', ');

            return redirect()->back()->withErrors(['start_time' => "El horario se solapa con: {$conflicts}"]);
        }
        
        DoctorSchedule::create($request->all());
        
        return redirect()->back()->with('success', 'Horario creado exitosamente');
    }

    public function update(Request $request, $schedule)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }
        
        // Buscar manualmente el schedule (parece que el route-model binding no está resolviendo)
        $scheduleModel = DoctorSchedule::find($schedule);
        if (!$scheduleModel) {
            \Illuminate\Support\Facades\Log::warning('DoctorScheduleController.update - schedule not found', ['schedule_param' => $schedule]);
            return redirect()->back()->withErrors(['schedule' => 'Horario no encontrado.']);
        }

        // Si es doctor, verificar que solo edite sus propios horarios
        if ($user->hasRole('medico') && $user->doctor->id != $scheduleModel->doctor_id) {
            abort(403, 'No puedes editar horarios de otros doctores');
        }
        
        $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'appointment_duration' => 'required|integer|min:15|max:120',
            'is_active' => 'boolean',
        ]);
        
        // Logging diagnóstico antes de validar especialidad
        try {
            \Illuminate\Support\Facades\Log::info('DoctorScheduleController.update - diagnostic', [
                'schedule_id' => $scheduleModel->id,
                'schedule_doctor_id' => $scheduleModel->doctor_id,
                'request_specialty_id' => $request->specialty_id,
            ]);
        } catch (\Exception $e) {
            // no bloquear en caso de fallo del logger
        }

        // Validar que el doctor exista y tenga la especialidad seleccionada
    $doctor = Doctor::with('specialties')->find($scheduleModel->doctor_id);
        try {
            \Illuminate\Support\Facades\Log::info('DoctorScheduleController.update - doctor loaded', [
                'doctor' => $doctor ? ['id' => $doctor->id, 'specialty_ids' => $doctor->specialties ? $doctor->specialties->pluck('id') : null] : null,
            ]);
        } catch (\Exception $e) {}

    if (!$doctor || !$doctor->specialties || !$doctor->specialties->contains('id', $request->specialty_id)) {
            return redirect()->back()->withErrors(['specialty_id' => 'El doctor no tiene asignada esta especialidad o no existe.']);
        }
        
        // Verificar conflictos de horario (excluyendo el horario actual)
        $hasConflict = DoctorSchedule::hasScheduleConflict(
            $scheduleModel->doctor_id,
            $scheduleModel->day_of_week,
            $request->start_time,
            $request->end_time,
            $scheduleModel->id
        );
        
        if ($hasConflict) {
            $conflictingSchedules = DoctorSchedule::getConflictingSchedules(
                $scheduleModel->doctor_id,
                $scheduleModel->day_of_week,
                $request->start_time,
                $request->end_time,
                $scheduleModel->id
            );

            $conflicts = $conflictingSchedules->map(function($conflictSchedule) {
                $specName = optional($conflictSchedule->specialty)->name ?? 'Sin especialidad';
                return $specName . ' (' . $conflictSchedule->start_time . ' - ' . $conflictSchedule->end_time . ')';
            })->implode(', ');

            return redirect()->back()->withErrors(['start_time' => "El horario se solapa con: {$conflicts}"]);
        }
        
    $scheduleModel->update($request->all());
        
        return redirect()->back()->with('success', 'Horario actualizado exitosamente');
    }

    public function destroy(DoctorSchedule $schedule)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }
        
        // Si es doctor, verificar que solo elimine sus propios horarios
        if ($user->hasRole('medico') && $user->doctor->id != $schedule->doctor_id) {
            abort(403, 'No puedes eliminar horarios de otros doctores');
        }
        
        $schedule->delete();
        
        return redirect()->back()->with('success', 'Horario eliminado exitosamente');
    }

    // API para obtener horarios disponibles de un doctor en una fecha específica
    public function getAvailableSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'required|exists:specialties,id',
            'date' => 'required|date|after_or_equal:today',
        ]);
        
        $doctor = Doctor::findOrFail($request->doctor_id);
        $date = new \DateTime($request->date);
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        
        // Obtener horario del doctor para ese día y especialidad específica
        $schedule = $doctor->schedules()
            ->active()
            ->forDay($dayOfWeek)
            ->where('specialty_id', $request->specialty_id)
            ->first();
            
        if (!$schedule) {
            return response()->json(['slots' => [], 'message' => 'Doctor no disponible este día para esta especialidad']);
        }
        
        // Generar slots disponibles
        $allSlots = $schedule->getAvailableTimeSlots();
        
        // Obtener citas ya programadas para esa fecha y especialidad
        $bookedSlots = $doctor->appointments()
            ->whereDate('appointment_date', $request->date)
            ->where('specialty_id', $request->specialty_id)
            ->whereNotIn('status', ['cancelada'])
            ->pluck('appointment_date')
            ->map(function ($dateTime) {
                return date('H:i', strtotime($dateTime));
            })
            ->toArray();
        
        // Filtrar slots disponibles
        $availableSlots = array_diff($allSlots, $bookedSlots);
        
        return response()->json([
            'slots' => array_values($availableSlots),
            'duration' => $schedule->appointment_duration,
        ]);
    }

    // API para obtener especialidades de un doctor
    public function getDoctorSpecialties(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);
        
        $doctor = Doctor::with('specialties')->findOrFail($request->doctor_id);
        
        return response()->json([
            'specialties' => $doctor->specialties
        ]);
    }
}
