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
    public function index()
    {
        $user = Auth::user();
        
        // Solo admin y doctores pueden gestionar horarios
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para ver esta página');
        }
        
        // Si es doctor, solo puede ver/editar sus propios horarios
        if ($user->hasRole('medico')) {
            $doctor = $user->doctor;
            $schedules = $doctor->schedules()->with('specialty')->orderBy('day_of_week')->get();
            $doctors = collect([$doctor]);
        } else {
            // Admin puede ver todos los doctores
            $doctors = Doctor::with(['user', 'schedules', 'specialties'])->get();
            $schedules = DoctorSchedule::with(['doctor.user', 'specialty'])->orderBy('doctor_id')->orderBy('day_of_week')->get();
        }
        
        // Obtener todas las especialidades activas
        $specialties = Specialty::where('is_active', true)->get();
        
        return Inertia::render('DoctorSchedules/Index', [
            'schedules' => $schedules,
            'doctors' => $doctors,
            'specialties' => $specialties,
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
        
        // Validar que el doctor tenga la especialidad seleccionada
        $doctor = Doctor::with('specialties')->find($request->doctor_id);
        if (!$doctor->specialties->contains($request->specialty_id)) {
            return redirect()->back()->withErrors(['specialty_id' => 'El doctor no tiene asignada esta especialidad.']);
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
                return $schedule->specialty->name . ' (' . $schedule->start_time . ' - ' . $schedule->end_time . ')';
            })->implode(', ');

            return redirect()->back()->withErrors(['start_time' => "El horario se solapa con: {$conflicts}"]);
        }
        
        DoctorSchedule::create($request->all());
        
        return redirect()->back()->with('success', 'Horario creado exitosamente');
    }

    public function update(Request $request, DoctorSchedule $schedule)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['administrador', 'medico'])) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }
        
        // Si es doctor, verificar que solo edite sus propios horarios
        if ($user->hasRole('medico') && $user->doctor->id != $schedule->doctor_id) {
            abort(403, 'No puedes editar horarios de otros doctores');
        }
        
        $request->validate([
            'specialty_id' => 'required|exists:specialties,id',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'appointment_duration' => 'required|integer|min:15|max:120',
            'is_active' => 'boolean',
        ]);
        
        // Validar que el doctor tenga la especialidad seleccionada
        $doctor = Doctor::with('specialties')->find($schedule->doctor_id);
        if (!$doctor->specialties->contains($request->specialty_id)) {
            return redirect()->back()->withErrors(['specialty_id' => 'El doctor no tiene asignada esta especialidad.']);
        }
        
        // Verificar conflictos de horario (excluyendo el horario actual)
        $hasConflict = DoctorSchedule::hasScheduleConflict(
            $schedule->doctor_id,
            $schedule->day_of_week,
            $request->start_time,
            $request->end_time,
            $schedule->id
        );
        
        if ($hasConflict) {
            $conflictingSchedules = DoctorSchedule::getConflictingSchedules(
                $schedule->doctor_id,
                $schedule->day_of_week,
                $request->start_time,
                $request->end_time,
                $schedule->id
            );

            $conflicts = $conflictingSchedules->map(function($conflictSchedule) {
                return $conflictSchedule->specialty->name . ' (' . $conflictSchedule->start_time . ' - ' . $conflictSchedule->end_time . ')';
            })->implode(', ');

            return redirect()->back()->withErrors(['start_time' => "El horario se solapa con: {$conflicts}"]);
        }
        
        $schedule->update($request->all());
        
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
