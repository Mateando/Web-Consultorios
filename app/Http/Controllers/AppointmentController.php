<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Verificar que el usuario esté autenticado
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Base query con relaciones
        $query = Appointment::with(['patient.user', 'doctor.user', 'specialty']);
        
        // Filtrar según el rol del usuario
        if ($user->hasRole('medico')) {
            $query->where('doctor_id', $user->doctor->id);
        } elseif ($user->hasRole('paciente')) {
            $query->where('patient_id', $user->patient->id);
        }
        // Admin o recepcionista pueden ver todas las citas (sin filtro adicional)

        // Aplicar filtros de búsqueda
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->filled('specialty_id')) {
            $query->where('specialty_id', $request->specialty_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('appointment_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('appointment_date', '<=', $request->end_date);
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->get();

        // Formatear las citas para el calendario
        $calendarEvents = $appointments->map(function ($appointment) {
            $startDate = Carbon::parse($appointment->appointment_date);
            $endDate = $startDate->copy()->addMinutes($appointment->duration ?? 30);
            
            return [
                'id' => $appointment->id,
                'title' => $appointment->patient->user->name . ' - ' . $appointment->doctor->user->name,
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
                'backgroundColor' => $this->getStatusColor($appointment->status),
                'borderColor' => $this->getStatusColor($appointment->status),
                'extendedProps' => [
                    'appointment' => $appointment,
                    'patient' => $appointment->patient->user->name,
                    'doctor' => $appointment->doctor->user->name,
                    'specialty' => $appointment->specialty->name ?? 'General',
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                ]
            ];
        });

        // Sólo incluir médicos activos que además tengan horarios activos configurados
        $doctors = Doctor::active()
            ->whereHas('schedules', function($q) { $q->where('is_active', true); })
            ->with(['user', 'specialties'])
            ->get();
        $patients = Patient::active()->with('user')->get();
        // Sólo incluir especialidades que tengan al menos un médico activo con horarios para esa misma especialidad
        $specialties = Specialty::active()
            ->with(['doctors.user', 'doctors.schedules'])
            ->get()
            ->filter(function($sp) {
                foreach ($sp->doctors as $doc) {
                    if (!$doc->user || !$doc->user->is_active) continue;
                    foreach ($doc->schedules as $sch) {
                        if ($sch->is_active && $sch->specialty_id == $sp->id) {
                            return true;
                        }
                    }
                }
                return false;
            })->values();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'calendar_events' => $calendarEvents,
            'doctors' => $doctors,
            'patients' => $patients,
            'specialties' => $specialties,
            'filters' => $request->only(['doctor_id', 'specialty_id', 'status', 'start_date', 'end_date']),
            'user_permissions' => [
                'can_create_appointments' => $user->hasRole(['administrador', 'medico', 'recepcionista']),
                'can_edit_appointments' => $user->hasRole(['administrador', 'medico', 'recepcionista']),
                'can_delete_appointments' => $user->hasRole(['administrador', 'medico']),
                'can_cancel_own_appointments' => $user->hasRole('paciente'),
                'can_edit_own_appointments' => $user->hasRole('paciente'), // Pacientes pueden editar con restricciones
                'is_patient' => $user->hasRole('paciente'),
            ],
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        
        // Solo admin, doctores y recepcionistas pueden crear citas
        if (!$user->hasRole(['administrador', 'medico', 'recepcionista'])) {
            abort(403, 'No tienes permisos para crear citas.');
        }
        
        // Sólo incluir médicos con horarios activos (no mostrar médicos sin horarios configurados)
        $doctors = Doctor::active()
            ->whereHas('schedules', function($q) { $q->where('is_active', true); })
            ->with(['user', 'specialties'])
            ->get();
        $patients = Patient::active()->with('user')->get();
        // Filtrar especialidades que tienen al menos un doctor activo con horario para esa especialidad
        $specialties = Specialty::active()
            ->with(['doctors.user', 'doctors.schedules'])
            ->get()
            ->filter(function($sp) {
                foreach ($sp->doctors as $doc) {
                    if (!$doc->user || !$doc->user->is_active) continue;
                    foreach ($doc->schedules as $sch) {
                        if ($sch->is_active && $sch->specialty_id == $sp->id) {
                            return true;
                        }
                    }
                }
                return false;
            })->values();
        
        return Inertia::render('Appointments/Create', [
            'doctors' => $doctors,
            'patients' => $patients,
            'specialties' => $specialties,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Solo admin, doctores y recepcionistas pueden crear citas
        if (!$user->hasRole(['administrador', 'medico', 'recepcionista'])) {
            abort(403, 'No tienes permisos para crear citas.');
        }
        
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'appointment_date' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:240',
            'notes' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:1000',
            'status' => 'nullable|in:programada,confirmada,en_curso,completada,cancelada,no_asistio',
        ]);

        // Validaciones adicionales para usuarios activos
        $patient = Patient::with('user')->find($request->patient_id);
        if (!$patient || !$patient->user->is_active) {
            return redirect()->back()->withErrors(['patient_id' => 'El paciente seleccionado está inactivo y no puede ser asignado a citas.']);
        }

        $doctor = Doctor::with(['user', 'specialties'])->find($request->doctor_id);
        if (!$doctor || !$doctor->user->is_active) {
            return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado está inactivo y no puede ser asignado a citas.']);
        }

        // Verificar que el doctor tenga especialidades activas
        $hasActiveSpecialties = $doctor->specialties()->where('is_active', true)->exists();
        if (!$hasActiveSpecialties) {
            return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado no tiene especialidades activas.']);
        }

        // Si se especifica una especialidad, verificar que el doctor la tenga y esté activa
        if ($request->specialty_id) {
            $specialty = $doctor->specialties()->where('specialties.id', $request->specialty_id)->where('is_active', true)->first();
            if (!$specialty) {
                return redirect()->back()->withErrors(['specialty_id' => 'El médico seleccionado no tiene la especialidad especificada o la especialidad está inactiva.']);
            }
        }

        // NUEVA VALIDACIÓN: Verificar que la fecha y hora estén dentro de alguno de los horarios
        // activos del doctor para ese día (y especialidad si se especificó). Soportar múltiples
        // franjas en el mismo día (por ejemplo mañana y tarde).
        $appointmentDate = Carbon::parse($request->appointment_date);
        $dayOfWeek = strtolower($appointmentDate->format('l')); // monday, tuesday, etc.
        $timeSlot = $appointmentDate->format('H:i');

        // Construir query de horarios que coincidan en día y estén activos. Si se indicó
        // specialty_id, filtrar también por ella para evitar validar contra horarios de otra especialidad.
        $schedulesQuery = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true);

        if ($request->specialty_id) {
            $schedulesQuery->where('specialty_id', $request->specialty_id);
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            return redirect()->back()->withErrors([
                'appointment_date' => "El doctor {$doctor->user->name} no atiende los " . $this->translateDayToSpanish($dayOfWeek) . " para la especialidad seleccionada."
            ]);
        }

        // Generar todos los slots posibles combinando cada horario activo
        $allSlots = [];
        foreach ($schedules as $sch) {
            $slotsForSchedule = $sch->getAvailableTimeSlots();
            if (!empty($slotsForSchedule)) {
                $allSlots = array_merge($allSlots, $slotsForSchedule);
            }
        }

        // Normalizar: quitar duplicados y ordenar
        $allSlots = array_values(array_unique($allSlots));
        sort($allSlots);

        // Si el slot seleccionado no está entre los slots posibles, construir mensaje útil
        if (!in_array($timeSlot, $allSlots)) {
            // Evaluar si la hora cae dentro de alguna franja horaria (pero no coincide con un slot
            // válido por la duración), o si está fuera de todas las franjas.
            $fallsWithinInterval = false;
            $intervals = [];
            $refSchedule = null;
            foreach ($schedules as $sch) {
                $start = Carbon::parse($sch->start_time)->format('H:i');
                $end = Carbon::parse($sch->end_time)->format('H:i');
                $intervals[] = "{$start} - {$end}";
                if ($timeSlot >= $start && $timeSlot < $end) {
                    $fallsWithinInterval = true;
                    $refSchedule = $sch;
                    break;
                }
            }

            if ($fallsWithinInterval && $refSchedule) {
                return redirect()->back()->withErrors([
                    'appointment_date' => "La hora seleccionada ({$timeSlot}) no corresponde a un horario de cita válido para la duración configurada. Las citas son cada {$refSchedule->appointment_duration} minutos."
                ]);
            } else {
                $intervalText = implode(' ; ', $intervals);
                return redirect()->back()->withErrors([
                    'appointment_date' => "La hora seleccionada ({$timeSlot}) está fuera del horario de atención del doctor ({$intervalText})."
                ]);
            }
        }
        
        // Verificar que no exista otra cita en el mismo horario
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $request->appointment_date)
            ->whereNotIn('status', ['cancelada'])
            ->first();
            
        if ($existingAppointment) {
            return redirect()->back()->withErrors([
                'appointment_date' => "Ya existe una cita programada para el doctor en este horario."
            ]);
        }

        $appointment = Appointment::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'specialty_id' => $request->specialty_id,
            'appointment_date' => $request->appointment_date,
            'duration' => $request->duration ?? 30,
            'notes' => $request->notes,
            'reason' => $request->reason,
            'status' => $request->status ?? 'programada',
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Cita creada exitosamente.');
    }

    public function show(Appointment $appointment)
    {
        $user = Auth::user();
        
        // Verificar permisos: admin/doctor/receptionist ven todas, pacientes solo las suyas
        if ($user->hasRole('paciente')) {
            if ($appointment->patient_id !== $user->patient->id) {
                abort(403, 'No tienes permisos para ver esta cita.');
            }
        } elseif (!$user->hasRole(['administrador', 'medico', 'recepcionista'])) {
            abort(403, 'No tienes permisos para ver esta cita.');
        }
        
        $appointment->load(['patient.user', 'doctor.user', 'specialty']);
        
        // Calcular si la cita puede ser editada por el paciente (más de 24 horas)
        $canEditForPatient = false;
        if ($user->hasRole('paciente')) {
            $appointmentDate = Carbon::parse($appointment->appointment_date);
            $now = Carbon::now();
            $canEditForPatient = $now->diffInHours($appointmentDate) >= 24 && 
                                !in_array($appointment->status, ['cancelada', 'completada']);
        }
        
        return Inertia::render('Appointments/Show', [
            'appointment' => $appointment,
            'can_edit_for_patient' => $canEditForPatient,
        ]);
    }

    public function edit(Appointment $appointment)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->hasRole('paciente')) {
            // Los pacientes pueden editar sus citas solo si faltan más de 24 horas
            if ($appointment->patient_id !== $user->patient->id) {
                abort(403, 'No tienes permisos para editar esta cita.');
            }
            
            $appointmentDate = Carbon::parse($appointment->appointment_date);
            $now = Carbon::now();
            
            if ($now->diffInHours($appointmentDate) < 24) {
                return redirect()->back()->with('error', 'Solo puedes editar citas con al menos 24 horas de anticipación.');
            }
            
            if (in_array($appointment->status, ['cancelada', 'completada'])) {
                return redirect()->back()->with('error', 'No puedes editar una cita cancelada o completada.');
            }
        } elseif (!$user->hasRole(['administrador', 'medico', 'recepcionista'])) {
            abort(403, 'No tienes permisos para editar citas.');
        }
        
        $appointment->load(['patient.user', 'doctor.user', 'specialty']);
        
        // Para pacientes, solo mostrar opciones limitadas
        if ($user->hasRole('paciente')) {
            return Inertia::render('Appointments/PatientEdit', [
                'appointment' => $appointment,
            ]);
        }
        
        // Para staff, vista completa de edición
        $doctors = Doctor::active()
            ->whereHas('schedules', function($q) { $q->where('is_active', true); })
            ->with(['user', 'specialties'])
            ->get();
        $patients = Patient::active()->with('user')->get();
        $specialties = Specialty::active()
            ->with(['doctors.user', 'doctors.schedules'])
            ->get()
            ->filter(function($sp) {
                foreach ($sp->doctors as $doc) {
                    if (!$doc->user || !$doc->user->is_active) continue;
                    foreach ($doc->schedules as $sch) {
                        if ($sch->is_active && $sch->specialty_id == $sp->id) {
                            return true;
                        }
                    }
                }
                return false;
            })->values();
        
        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            'doctors' => $doctors,
            'patients' => $patients,
            'specialties' => $specialties,
        ]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        
        // Verificar permisos
        if ($user->hasRole('paciente')) {
            // Los pacientes pueden actualizar sus citas solo si faltan más de 24 horas
            if ($appointment->patient_id !== $user->patient->id) {
                abort(403, 'No tienes permisos para editar esta cita.');
            }
            
            $appointmentDate = Carbon::parse($appointment->appointment_date);
            $now = Carbon::now();
            
            if ($now->diffInHours($appointmentDate) < 24) {
                abort(403, 'Solo puedes editar citas con al menos 24 horas de anticipación.');
            }
            
            if (in_array($appointment->status, ['cancelada', 'completada'])) {
                abort(403, 'No puedes editar una cita cancelada o completada.');
            }
            
            // Para pacientes, solo permitir cambio de estado
            $request->validate([
                'status' => 'required|in:confirmada,cancelada',
            ]);
            
            $appointment->update([
                'status' => $request->status,
            ]);
            
            $statusText = $request->status === 'confirmada' ? 'confirmada' : 'cancelada';
            return redirect()->route('appointments.index')->with('success', "Cita {$statusText} exitosamente.");
        } elseif (!$user->hasRole(['administrador', 'medico', 'recepcionista'])) {
            abort(403, 'No tienes permisos para editar citas.');
        }
        
        // Para staff, validación completa
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'nullable|exists:specialties,id',
            'appointment_date' => 'required|date',
            'duration' => 'nullable|integer|min:15|max:240',
            'notes' => 'nullable|string|max:1000',
            'reason' => 'nullable|string|max:1000',
            'status' => 'nullable|in:programada,confirmada,en_curso,completada,cancelada,no_asistio',
        ]);

        // Validaciones adicionales para usuarios activos
        $patient = Patient::with('user')->find($request->patient_id);
        if (!$patient || !$patient->user->is_active) {
            return redirect()->back()->withErrors(['patient_id' => 'El paciente seleccionado está inactivo y no puede ser asignado a citas.']);
        }

        $doctor = Doctor::with(['user', 'specialties'])->find($request->doctor_id);
        if (!$doctor || !$doctor->user->is_active) {
            return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado está inactivo y no puede ser asignado a citas.']);
        }

        // Verificar que el doctor tenga especialidades activas
        $hasActiveSpecialties = $doctor->specialties()->where('is_active', true)->exists();
        if (!$hasActiveSpecialties) {
            return redirect()->back()->withErrors(['doctor_id' => 'El médico seleccionado no tiene especialidades activas.']);
        }

        // Si se especifica una especialidad, verificar que el doctor la tenga y esté activa
        if ($request->specialty_id) {
            $specialty = $doctor->specialties()->where('specialties.id', $request->specialty_id)->where('is_active', true)->first();
            if (!$specialty) {
                return redirect()->back()->withErrors(['specialty_id' => 'El médico seleccionado no tiene la especialidad especificada o la especialidad está inactiva.']);
            }
        }

        $appointment->update([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'specialty_id' => $request->specialty_id,
            'appointment_date' => $request->appointment_date,
            'duration' => $request->duration ?? 30,
            'notes' => $request->notes,
            'reason' => $request->reason,
            'status' => $request->status ?? 'programada',
        ]);

        return redirect()->back()->with('success', 'Cita actualizada exitosamente.');
    }

    public function destroy(Appointment $appointment)
    {
        $user = Auth::user();
        
        // Solo admin y doctores pueden eliminar citas
        if (!$user->hasRole(['administrador', 'medico'])) {
            return redirect()->back()->withErrors(['error' => 'No tienes permisos para eliminar citas.']);
        }
        
        $appointment->delete();
        return redirect()->back()->with('success', 'Cita eliminada exitosamente.');
    }

    /**
     * Cancelar cita para pacientes
     */
    public function cancel(Appointment $appointment)
    {
        $user = Auth::user();
        
        // Verificar que el usuario sea paciente y sea dueño de la cita
        if (!$user->hasRole('paciente') || $appointment->patient_id !== $user->patient->id) {
            return redirect()->back()->withErrors(['error' => 'No tienes permisos para cancelar esta cita.']);
        }
        
        // Verificar que la cita sea con al menos 1 día de anticipación
        $appointmentDate = Carbon::parse($appointment->appointment_date);
        $now = Carbon::now();
        
        if ($now->diffInHours($appointmentDate) < 24) {
            return redirect()->back()->withErrors(['error' => 'Solo puedes cancelar citas con al menos 24 horas de anticipación.']);
        }
        
        // Verificar que la cita no esté ya cancelada
        if ($appointment->status === 'cancelada') {
            return redirect()->back()->withErrors(['error' => 'Esta cita ya está cancelada.']);
        }
        
        // Cambiar estado a cancelada
        $appointment->update(['status' => 'cancelada']);
        
        return redirect()->back()->with('success', 'Cita cancelada exitosamente.');
    }

    /**
     * Obtener doctores por especialidad que atienden en una fecha específica
     */
    public function getDoctorsBySpecialty(Request $request)
    {
        $specialtyId = $request->query('specialty_id');
        $date = $request->query('date');
        
        // Obtener el día de la semana de la fecha seleccionada
        $dayOfWeek = null;
        if ($date) {
            $dayOfWeek = strtolower(Carbon::parse($date)->format('l')); // monday, tuesday, etc.
        }
        
        if (!$specialtyId) {
            return response()->json(['doctors' => []]);
        }
        
    $query = Doctor::withSpecialty($specialtyId)->with(['user', 'specialties']);
        
        // Filtrar doctores activos
        $query->whereHas('user', function($q) {
            $q->where('is_active', true);
        });

        // Excluir doctores que no tengan horarios activos para esta especialidad
        $query->whereHas('schedules', function($q) use ($specialtyId) {
            $q->where('specialty_id', $specialtyId)
              ->where('is_active', true);
        });
        
        // Si se proporciona una fecha, filtrar por doctores que atienden ese día Y especialidad
        if ($dayOfWeek) {
            $query->whereHas('schedules', function($q) use ($dayOfWeek, $specialtyId) {
                $q->where('day_of_week', $dayOfWeek)
                  ->where('specialty_id', $specialtyId)
                  ->where('is_active', true);
            });
        }

        $doctors = $query->get();

        return response()->json([
            'doctors' => $doctors->map(function ($doctor) use ($date, $dayOfWeek, $specialtyId) {
                $doctorData = [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'consultation_fee' => $doctor->consultation_fee,
                    'specialties' => $doctor->specialties->pluck('name')->toArray(),
                ];

                // Agregar información de disponibilidad si se proporciona fecha
                if ($date && $dayOfWeek) {
                    $schedule = $doctor->schedules()
                        ->where('day_of_week', $dayOfWeek)
                        ->where('specialty_id', $specialtyId)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($schedule) {
                        $doctorData['schedule'] = [
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'appointment_duration' => $schedule->appointment_duration,
                        ];
                        
                        // Obtener citas ya programadas para ese día y especialidad
                        $bookedCount = $doctor->appointments()
                            ->whereDate('appointment_date', $date)
                            ->where('specialty_id', $specialtyId)
                            ->whereNotIn('status', ['cancelada'])
                            ->count();
                        
                        $totalSlots = count($schedule->getAvailableTimeSlots());
                        $availableSlots = $totalSlots - $bookedCount;
                        
                        $doctorData['availability'] = [
                            'total_slots' => $totalSlots,
                            'booked_slots' => $bookedCount,
                            'available_slots' => max(0, $availableSlots),
                            'is_available' => $availableSlots > 0
                        ];
                    } else {
                        $doctorData['availability'] = [
                            'total_slots' => 0,
                            'booked_slots' => 0,
                            'available_slots' => 0,
                            'is_available' => false
                        ];
                    }
                }

                return $doctorData;
            })->values()
        ]);
    }

    /**
     * Obtener slots de tiempo disponibles para un doctor en una fecha específica
     */
    public function getAvailableTimeSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'specialty_id' => 'required|exists:specialties,id',
            'date' => 'required|date|after_or_equal:today',
            'editing_appointment_id' => 'nullable|exists:appointments,id',
        ]);
        
        $doctor = Doctor::findOrFail($request->doctor_id);
        $date = Carbon::parse($request->date);
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.
        
        // Validar que el doctor tenga la especialidad seleccionada
        if (!$doctor->specialties->contains($request->specialty_id)) {
            return response()->json([
                'slots' => [], 
                'message' => 'El doctor no atiende esta especialidad',
                'duration' => 0
            ]);
        }
        
        // Obtener todos los horarios del doctor para ese día y especialidad específica
        $schedules = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('specialty_id', $request->specialty_id)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'slots' => [], 
                'message' => 'Doctor no disponible este día para esta especialidad',
                'duration' => 0
            ]);
        }

        // Generar todos los slots posibles combinando cada horario activo
        $allSlots = [];
        $durations = [];
        foreach ($schedules as $sch) {
            $slotsForSchedule = $sch->getAvailableTimeSlots();
            if (!empty($slotsForSchedule)) {
                $allSlots = array_merge($allSlots, $slotsForSchedule);
            }
            $durations[] = $sch->appointment_duration ?? null;
        }

        // Normalizar: quitar duplicados y ordenar
        $allSlots = array_values(array_unique($allSlots));
        sort($allSlots);
        
        // Obtener citas ya programadas para esa fecha y especialidad (excluyendo la cita que se está editando)
        $query = $doctor->appointments()
            ->whereDate('appointment_date', $request->date)
            ->where('specialty_id', $request->specialty_id)
            ->whereNotIn('status', ['cancelada']);
            
        // Si estamos editando una cita, excluirla de las citas ocupadas
        if ($request->editing_appointment_id) {
            $query->where('id', '!=', $request->editing_appointment_id);
        }
        
        $bookedSlots = $query->get()
            ->map(function ($appointment) {
                return Carbon::parse($appointment->appointment_date)->format('H:i');
            })
            ->toArray();
        
        // Filtrar slots disponibles
        $availableSlots = array_values(array_diff($allSlots, $bookedSlots));
        
        // Si la fecha es hoy, eliminar los slots que ya pasaron respecto al tiempo actual
        try {
            $requestedDate = Carbon::parse($request->date);
            if ($requestedDate->isToday()) {
                $nowTime = Carbon::now()->format('H:i');
                $availableSlots = array_values(array_filter($availableSlots, function ($slot) use ($nowTime) {
                    return $slot > $nowTime; // mantener solo slots estrictamente posteriores al momento actual
                }));
            }
        } catch (\Exception $e) {
            // Si parse falla, no aplicar el filtro de tiempo
        }

        // Usar el primer schedule como referencia para duration y meta info
        $referenceSchedule = $schedules->first();

        return response()->json([
            'slots' => $availableSlots,
            'duration' => $referenceSchedule->appointment_duration,
            'doctor_name' => $doctor->user->name,
            'specialty_name' => $referenceSchedule->specialty->name,
            'schedule_info' => [
                'start_time' => $referenceSchedule->start_time,
                'end_time' => $referenceSchedule->end_time,
                'day' => $dayOfWeek
            ]
        ]);
    }

    private function getStatusColor($status)
    {
        return match($status) {
            'programada' => '#3B82F6', // Azul
            'confirmada' => '#10B981', // Verde
            'en_curso' => '#F59E0B', // Amarillo
            'completada' => '#6B7280', // Gris
            'cancelada' => '#EF4444', // Rojo
            'no_asistio' => '#9CA3AF', // Gris claro
            default => '#6B7280', // Gris por defecto
        };
    }

    private function translateDayToSpanish($dayOfWeek)
    {
        return match($dayOfWeek) {
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miércoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sábados',
            'sunday' => 'domingos',
            default => $dayOfWeek,
        };
    }

    /**
     * Obtener días disponibles para una especialidad específica
     */
    public function getSpecialtyAvailableDays(Request $request)
    {
        $specialtyId = $request->get('specialty_id');
        
        if (!$specialtyId) {
            return response()->json([
                'available_days' => [],
                'message' => 'specialty_id es requerido'
            ], 400);
        }

        try {
            // Obtener todos los doctores de la especialidad con sus horarios
            $doctors = Doctor::active()
                ->whereHas('specialties', function ($query) use ($specialtyId) {
                    $query->where('specialties.id', $specialtyId);
                })
                ->with(['schedules' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get();

            $availableDays = [];
            
            // Recopilar todos los días donde hay al menos un doctor disponible
            foreach ($doctors as $doctor) {
                foreach ($doctor->schedules as $schedule) {
                    if (!in_array($schedule->day_of_week, $availableDays)) {
                        $availableDays[] = $schedule->day_of_week;
                    }
                }
            }

            // Convertir nombres de días a números para JavaScript (0 = domingo, 1 = lunes, etc.)
            $dayNumbers = [];
            foreach ($availableDays as $day) {
                $dayNumber = match($day) {
                    'sunday' => 0,
                    'monday' => 1,
                    'tuesday' => 2,
                    'wednesday' => 3,
                    'thursday' => 4,
                    'friday' => 5,
                    'saturday' => 6,
                    default => null,
                };
                
                if ($dayNumber !== null) {
                    $dayNumbers[] = $dayNumber;
                }
            }

            return response()->json([
                'available_days' => $dayNumbers,
                'available_day_names' => $availableDays,
                'doctors_count' => $doctors->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'available_days' => [],
                'error' => 'Error al obtener días disponibles: ' . $e->getMessage()
            ], 500);
        }
    }
}
