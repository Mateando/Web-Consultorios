<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index()
    {
        try {
            // Obtener pacientes con paginación
            $patientsQuery = Patient::with(['user'])
                ->whereHas('user', function ($query) {
                    $query->orderBy('name');
                })
                ->paginate(10);

            Log::info('PatientController@index', [
                'patients_count' => $patientsQuery->count(),
                'patients_total' => $patientsQuery->total(),
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()->getRoleNames(),
                'first_patient' => $patientsQuery->first() ? $patientsQuery->first()->user->name : 'No patients'
            ]);

            return Inertia::render('Patients/Index', [
                'patients' => $patientsQuery,
                'filters' => request()->only(['search'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PatientController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function create()
    {
        // No necesitamos vista separada, usamos modal
        return redirect()->route('patients.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'document_type' => 'required|in:cedula,pasaporte,tarjeta_identidad,registro_civil',
            'document_number' => 'required|string|unique:users,document_number',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'gender' => 'required|in:masculino,femenino,otro',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_number' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'medications' => 'nullable|string',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password123'),
            'phone' => $request->phone,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('paciente');

        // Crear paciente
        Patient::create([
            'user_id' => $user->id,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'insurance_provider' => $request->insurance_provider,
            'insurance_number' => $request->insurance_number,
            'allergies' => $request->allergies,
            'medical_conditions' => $request->medical_conditions,
            'medications' => $request->medications,
            'blood_type' => $request->blood_type,
            'height' => $request->height,
            'weight' => $request->weight,
        ]);

        return redirect()->back()->with('success', 'Paciente creado exitosamente');
    }

    public function show(Patient $patient)
    {
        $patient->load('user', 'appointments.doctor.user');
        
        return Inertia::render('Patients/Show', [
            'patient' => $patient
        ]);
    }

    public function edit(Patient $patient)
    {
        // No necesitamos vista separada, usamos modal
        return redirect()->route('patients.index');
    }

    public function update(Request $request, Patient $patient)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($patient->user->id)],
            'document_type' => 'required|in:cedula,pasaporte,tarjeta_identidad,registro_civil',
            'document_number' => ['required', 'string', Rule::unique('users')->ignore($patient->user->id)],
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'gender' => 'required|in:masculino,femenino,otro',
            'address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_number' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'medications' => 'nullable|string',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:10|max:300',
        ]);

        // Actualizar usuario
        $patient->user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
        ]);

        // Actualizar paciente
        $patient->update([
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'insurance_provider' => $request->insurance_provider,
            'insurance_number' => $request->insurance_number,
            'allergies' => $request->allergies,
            'medical_conditions' => $request->medical_conditions,
            'medications' => $request->medications,
            'blood_type' => $request->blood_type,
            'height' => $request->height,
            'weight' => $request->weight,
        ]);

        return redirect()->back()->with('success', 'Paciente actualizado exitosamente');
    }

    public function destroy(Patient $patient)
    {
        // No eliminamos, sino que desactivamos al paciente
        $patient->user->update(['is_active' => false]);
        return redirect()->back()->with('success', 'Paciente desactivado exitosamente');
    }

    /**
     * Toggle patient status (activate/deactivate).
     */
    public function toggleStatus(Patient $patient)
    {
        try {
            $newStatus = $patient->user->is_active ? false : true;
            $patient->user->update(['is_active' => $newStatus]);

            $message = $newStatus ? 'activado' : 'desactivado';
            return redirect()->back()
                ->with('success', "Paciente {$message} exitosamente.");

        } catch (\Exception $e) {
            Log::error('Error toggling patient status: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al cambiar el estado del paciente.');
        }
    }
}
