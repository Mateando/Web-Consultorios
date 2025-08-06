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
        
        $doctors = Doctor::with(['user', 'specialties', 'appointments.patient.user'])
            ->paginate(10);
        
        return Inertia::render('Doctors/Index', [
            'doctors' => $doctors,
            'specialties' => Specialty::where('is_active', true)->get(),
            'filters' => request()->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
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

        // Asignar especialidades al doctor
        $doctor->specialties()->sync($request->specialties);

        return redirect()->back()->with('success', 'Doctor creado exitosamente');
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $doctor->user_id,
            'phone' => 'nullable|string|max:20',
            'specialties' => 'required|array|min:1',
            'specialties.*' => 'exists:specialties,id',
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

        // Sincronizar especialidades
        $doctor->specialties()->sync($request->specialties);

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
}
