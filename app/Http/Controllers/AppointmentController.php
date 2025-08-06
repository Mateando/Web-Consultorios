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
        if ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->doctor->id);
        } elseif ($user->hasRole('patient')) {
            $query->where('patient_id', $user->patient->id);
        }
        // Admin o recepcionista pueden ver todas las citas (sin filtro adicional)

        // Aplicar filtros de búsqueda
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
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

        $doctors = Doctor::active()->with(['user', 'specialties'])->get();
        $patients = Patient::active()->with('user')->get();
        $specialties = Specialty::active()->get();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'calendar_events' => $calendarEvents,
            'doctors' => $doctors,
            'patients' => $patients,
            'specialties' => $specialties,
            'filters' => $request->only(['doctor_id', 'status', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
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

    public function update(Request $request, Appointment $appointment)
    {
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
        $appointment->delete();
        return redirect()->back()->with('success', 'Cita eliminada exitosamente.');
    }

    /**
     * Obtener doctores por especialidad
     */
    public function getDoctorsBySpecialty(Request $request)
    {
        $specialtyId = $request->query('specialty_id');
        
        if (!$specialtyId) {
            $doctors = Doctor::with('user')->whereHas('user', function($query) {
                $query->where('is_active', true);
            })->get();
        } else {
            $doctors = Doctor::withSpecialty($specialtyId)
                ->with(['user', 'specialties'])
                ->whereHas('user', function($query) {
                    $query->where('is_active', true);
                })
                ->get();
        }

        return response()->json([
            'doctors' => $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'consultation_fee' => $doctor->consultation_fee,
                    'license_number' => $doctor->license_number,
                    'specialties' => $doctor->specialties->map(function ($specialty) {
                        return [
                            'id' => $specialty->id,
                            'name' => $specialty->name,
                        ];
                    }),
                ];
            }),
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
}
