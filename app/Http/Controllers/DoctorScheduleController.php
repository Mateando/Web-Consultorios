<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
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
            $schedules = $doctor->schedules()->orderBy('day_of_week')->get();
            $doctors = collect([$doctor]);
        } else {
            // Admin puede ver todos los doctores
            $doctors = Doctor::with(['user', 'schedules'])->get();
            $schedules = DoctorSchedule::with('doctor.user')->orderBy('doctor_id')->orderBy('day_of_week')->get();
        }
        
        return Inertia::render('DoctorSchedules/Index', [
            'schedules' => $schedules,
            'doctors' => $doctors,
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
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'appointment_duration' => 'required|integer|min:5|max:120',
        ]);
        
        // Si es doctor, verificar que solo edite sus propios horarios
        if ($user->hasRole('medico') && $user->doctor->id != $request->doctor_id) {
            abort(403, 'No puedes editar horarios de otros doctores');
        }
        
        // Verificar que no exista ya un horario para ese día
        $existingSchedule = DoctorSchedule::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $request->day_of_week)
            ->first();
            
        if ($existingSchedule) {
            return redirect()->back()->withErrors(['day_of_week' => 'Ya existe un horario para este día']);
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
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'appointment_duration' => 'required|integer|min:5|max:120',
            'is_active' => 'boolean',
        ]);
        
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
            'date' => 'required|date|after_or_equal:today',
        ]);
        
        $doctor = Doctor::findOrFail($request->doctor_id);
        $date = new \DateTime($request->date);
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        
        // Obtener horario del doctor para ese día
        $schedule = $doctor->schedules()
            ->active()
            ->forDay($dayOfWeek)
            ->first();
            
        if (!$schedule) {
            return response()->json(['slots' => [], 'message' => 'Doctor no disponible este día']);
        }
        
        // Generar slots disponibles
        $allSlots = $schedule->getAvailableTimeSlots();
        
        // Obtener citas ya programadas para esa fecha
        $bookedSlots = $doctor->appointments()
            ->whereDate('appointment_date', $request->date)
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
}
