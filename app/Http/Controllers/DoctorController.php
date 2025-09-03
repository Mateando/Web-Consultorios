<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Solo admin y recepcionistas pueden ver todos los doctores
        if (!$user->hasAnyRole(['administrador', 'recepcionista'])) {
            abort(403, 'No tienes permisos para ver esta página');
        }
        
        $search = trim($request->get('search', ''));
        $specialtyId = $request->get('specialty_id', '');

        $doctors = Doctor::query()
            ->select('doctors.*')
            ->with(['user', 'specialties', 'appointments.patient.user'])
            ->leftJoin('users', 'users.id', '=', 'doctors.user_id')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('users.name', 'like', "%{$search}%")
                       ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->when($specialtyId, function ($q) use ($specialtyId) {
                $q->whereHas('specialties', function ($qq) use ($specialtyId) {
                    $qq->where('specialties.id', $specialtyId);
                });
            })
            ->orderByRaw('LOWER(users.name)')
            ->paginate(6)
            ->withQueryString();

        return Inertia::render('Doctors/Index', [
            'doctors' => $doctors,
            'specialties' => Specialty::where('is_active', true)->get(),
            'filters' => ['search' => $search, 'specialty_id' => $specialtyId]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'specialty_id' => 'required|exists:specialties,id',
            'license_number' => 'required|string|max:50|unique:doctors',
            'consultation_fee' => 'nullable|numeric|min:0',
            'availability_schedule' => 'nullable|json',
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Asignar rol de médico
        $user->assignRole('medico');

        // Crear perfil de doctor
        $doctor = Doctor::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'consultation_fee' => $request->consultation_fee,
            'availability_schedule' => $request->availability_schedule,
        ]);

    // Asignar la especialidad principal al doctor
    $doctor->specialties()->sync([$request->specialty_id]);

        return redirect()->back()->with('success', 'Doctor creado exitosamente');
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $doctor->user_id,
            'phone' => 'nullable|string|max:20',
            'specialty_id' => 'required|exists:specialties,id',
            'license_number' => 'required|string|max:50|unique:doctors,license_number,' . $doctor->id,
            'consultation_fee' => 'nullable|numeric|min:0',
            'availability_schedule' => 'nullable|json',
        ]);

        // Actualizar usuario
        $doctor->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Actualizar doctor
        $doctor->update($request->except(['name', 'email', 'password', 'specialties']));

    // Sincronizar la especialidad (solo una)
    $doctor->specialties()->sync([$request->specialty_id]);

        return redirect()->back()->with('success', 'Doctor actualizado exitosamente');
    }

    public function destroy(Doctor $doctor)
    {
        // No eliminamos, sino que desactivamos al doctor
        $doctor->user->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Doctor desactivado exitosamente');
    }

    /**
     * Toggle doctor status (activate/deactivate).
     */
    public function toggleStatus(Doctor $doctor)
    {
        $newStatus = $doctor->user->is_active ? false : true;
        $doctor->user->update(['is_active' => $newStatus]);
        $message = $newStatus ? 'activado' : 'desactivado';
        return redirect()->back()->with('success', "Doctor {$message} exitosamente");
    }

    /**
     * Mostrar un doctor (método mínimo para satisfacer la ruta resource).
     * Si existe una vista Inertia `Doctors/Show` la renderiza, si no redirige a index.
     */
    public function show(Doctor $doctor)
    {
        // Solo roles con permisos pueden ver detalles
        $user = request()->user();
        if (!$user->hasAnyRole(['administrador', 'recepcionista', 'medico'])) {
            abort(403, 'No tienes permisos para ver esta página');
        }

        // Intentar renderizar una página Inertia si existe en el frontend.
        // Si el proyecto no tiene `Doctors/Show.vue`, redirigimos al índice.
        try {
            return Inertia::render('Doctors/Show', [
                'doctor' => $doctor->load(['user', 'specialties', 'insuranceProviders']),
            ]);
        } catch (\Exception $e) {
            // En caso de que no exista la página Vue u ocurra otro error, redirigimos al listado
            return redirect()->route('doctors.index');
        }
    }
}
