<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Solo administradores pueden acceder
        if (!$user->hasRole('administrador')) {
            abort(403, 'No tienes permisos para ver esta página');
        }
        
        // Estadísticas del dashboard
        $stats = [
            'total_users' => User::count(),
            'total_doctors' => Doctor::count(),
            'total_patients' => Patient::count(),
            'total_appointments' => Appointment::count(),
            'appointments_today' => Appointment::whereDate('appointment_date', today())->count(),
            'appointments_this_month' => Appointment::whereMonth('appointment_date', now()->month)->count(),
        ];
        
        // Citas recientes
        $recent_appointments = Appointment::with(['doctor.user', 'patient.user'])
            ->orderBy('appointment_date', 'desc')
            ->limit(5)
            ->get();
        
        // Doctores más activos (por número de citas)
        $active_doctors = Doctor::withCount('appointments')
            ->with('user')
            ->orderBy('appointments_count', 'desc')
            ->limit(5)
            ->get();
        
        // Citas por mes (últimos 6 meses)
        $appointments_by_month = Appointment::select(
                DB::raw('MONTH(appointment_date) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->where('appointment_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        return Inertia::render('Admin/Index', [
            'stats' => $stats,
            'recent_appointments' => $recent_appointments,
            'active_doctors' => $active_doctors,
            'appointments_by_month' => $appointments_by_month,
        ]);
    }
    
    public function users(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasRole('administrador')) {
            abort(403, 'No tienes permisos para ver esta página');
        }
        
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->paginate(15);

        // Obtener todos los roles disponibles
        $allRoles = Role::all();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'allRoles' => $allRoles,
        ]);
    }
    
    public function specialties()
    {
        $specialties = Specialty::withCount('doctors')->paginate(10);
        
        return Inertia::render('Admin/Specialties', [
            'specialties' => $specialties,
        ]);
    }
    
    public function storeSpecialty(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:specialties',
            'description' => 'nullable|string|max:500',
        ]);
        
        Specialty::create($request->only(['name', 'description']));
        
        return redirect()->back()->with('success', 'Especialidad creada exitosamente');
    }
    
    public function updateSpecialty(Request $request, Specialty $specialty)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:specialties,name,' . $specialty->id,
            'description' => 'nullable|string|max:500',
        ]);
        
        $specialty->update($request->only(['name', 'description']));
        
        return redirect()->back()->with('success', 'Especialidad actualizada exitosamente');
    }
    
    public function destroySpecialty(Specialty $specialty)
    {
        if ($specialty->doctors()->count() > 0) {
            return redirect()->back()->with('error', 'No se puede eliminar una especialidad que tiene doctores asignados');
        }
        
        $specialty->delete();
        
        return redirect()->back()->with('success', 'Especialidad eliminada exitosamente');
    }

    public function toggleSpecialtyStatus(Specialty $specialty)
    {
        $specialty->update([
            'is_active' => !$specialty->is_active
        ]);

        $status = $specialty->is_active ? 'activada' : 'desactivada';
        return redirect()->back()->with('success', "Especialidad {$status} correctamente");
    }
    
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());
        
        // Reporte de citas por estado
        $appointments_by_status = Appointment::whereBetween('appointment_date', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        
        // Reporte de ingresos por doctor
        $revenue_by_doctor = Doctor::with(['user', 'specialties'])
            ->withSum(['appointments' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('appointment_date', [$startDate, $endDate])
                      ->where('status', 'completada');
            }], 'consultation_fee')
            ->get();
        
        return Inertia::render('Admin/Reports', [
            'appointments_by_status' => $appointments_by_status,
            'revenue_by_doctor' => $revenue_by_doctor,
            'filters' => compact('startDate', 'endDate'),
        ]);
    }

    public function updateUserRoles(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Solo administradores pueden cambiar roles
        if (!$currentUser->hasRole('administrador')) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }

        // Validar que no se esté editando a sí mismo
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'No puedes cambiar tus propios roles');
        }

        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        try {
            DB::beginTransaction();

            // Remover todos los roles actuales
            $user->syncRoles([]);

            // Asignar los nuevos roles
            $user->syncRoles($request->roles);

            // Si se asigna rol de médico, crear/actualizar registro de doctor
            if (in_array('medico', $request->roles)) {
                if (!$user->doctor) {
                    Doctor::create([
                        'user_id' => $user->id,
                        'specialty_id' => null, // Se puede asignar después
                        'license_number' => null,
                        'consultation_fee' => 0,
                    ]);
                }
            } elseif ($user->doctor) {
                // Si se quita el rol de médico y tiene registro de doctor, eliminarlo
                $user->doctor->delete();
            }

            // Si se asigna rol de paciente, crear registro de paciente
            if (in_array('paciente', $request->roles)) {
                if (!$user->patient) {
                    Patient::create([
                        'user_id' => $user->id,
                    ]);
                }
            } elseif ($user->patient) {
                // Si se quita el rol de paciente y tiene registro de paciente, eliminarlo
                $user->patient->delete();
            }

            DB::commit();

            return redirect()->back()->with('success', 'Roles actualizados correctamente');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', 'Error al actualizar los roles: ' . $e->getMessage());
        }
    }

    public function toggleUserStatus(Request $request, User $user)
    {
        $currentUser = $request->user();
        
        // Solo administradores pueden cambiar estado
        if (!$currentUser->hasRole('administrador')) {
            abort(403, 'No tienes permisos para realizar esta acción');
        }

        // Validar que no se esté desactivando a sí mismo
        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'No puedes cambiar tu propio estado');
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Usuario {$status} correctamente");
    }
}
