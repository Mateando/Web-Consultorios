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
    public function index(Request $request)
    {
        try {
            $search = trim($request->get('search',''));

            $patients = Patient::query()
                ->select('patients.*')
                ->with('user')
                ->leftJoin('users','users.id','=','patients.user_id')
                ->when($search !== '', function($q) use ($search){
                    $q->where(function($qq) use ($search){
                        $qq->where('users.name','like',"%{$search}%")
                           ->orWhere('users.email','like',"%{$search}%")
                           ->orWhere('users.document_number','like',"%{$search}%");
                    });
                })
                ->orderByRaw('LOWER(users.name)')
                ->paginate(6)
                ->withQueryString();

            $first = $patients->first();

            return Inertia::render('Patients/Index', [
                'patients' => $patients,
                'filters' => ['search' => $search]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in PatientController@index (Inertia)', [
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
            'secondary_email' => 'nullable|email',
            'document_type' => 'required|in:cedula,pasaporte,tarjeta_identidad,registro_civil',
            'document_number' => 'required|string|unique:users,document_number',
            'phone' => 'nullable|string|max:20',
            'landline_phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'gender' => 'required|in:masculino,femenino,otro',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'patient_type' => 'nullable|string|max:100',
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
            'observations' => 'nullable|string',
            'extra_observations' => 'nullable|string',
        ]);

        // Crear usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'secondary_email' => $request->secondary_email,
            'password' => Hash::make('password123'),
            'phone' => $request->phone,
            'landline_phone' => $request->landline_phone,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'country' => $request->country,
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
            'patient_type' => $request->patient_type,
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
            'observations' => $request->observations,
            'extra_observations' => $request->extra_observations,
        ]);

        return redirect()->back()->with('success', 'Paciente creado exitosamente');
    }

    public function show(Patient $patient)
    {
    // Por ahora redirigimos al listado Livewire; se puede crear una vista detallada Livewire.
    return redirect()->route('patients.index');
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
            'secondary_email' => ['nullable','email', Rule::unique('users','secondary_email')->ignore($patient->user->id)],
            'document_type' => 'required|in:cedula,pasaporte,tarjeta_identidad,registro_civil',
            'document_number' => ['required', 'string', Rule::unique('users')->ignore($patient->user->id)],
            'phone' => 'nullable|string|max:20',
            'landline_phone' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'gender' => 'required|in:masculino,femenino,otro',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'patient_type' => 'nullable|string|max:100',
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
            'observations' => 'nullable|string',
            'extra_observations' => 'nullable|string',
        ]);

        // Actualizar usuario
        $patient->user->update([
            'name' => $request->name,
            'email' => $request->email,
            'secondary_email' => $request->secondary_email,
            'phone' => $request->phone,
            'landline_phone' => $request->landline_phone,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'country' => $request->country,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
        ]);

        // Actualizar paciente
        $patient->update([
            'patient_type' => $request->patient_type,
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
            'observations' => $request->observations,
            'extra_observations' => $request->extra_observations,
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

    /**
     * API endpoint para búsqueda de pacientes usado por selects dinámicos (retorna JSON).
     * Query params: q (string), limit (int)
     */
    public function apiSearch(Request $request)
    {
        $q = trim($request->get('q', ''));
        $limit = (int) $request->get('limit', 20);

        $query = Patient::query()
            ->with('user')
            ->leftJoin('users', 'users.id', '=', 'patients.user_id')
            ->select('patients.*')
            ->when($q !== '', function($qq) use ($q) {
                $qq->where(function($inner) use ($q) {
                    $inner->where('users.name', 'like', "%{$q}%")
                          ->orWhere('users.document_number', 'like', "%{$q}%")
                          ->orWhere('users.email', 'like', "%{$q}%");
                });
            })
            ->whereHas('user', function($u) {
                $u->where('is_active', true);
            })
            ->orderByRaw('LOWER(users.name)')
            ->limit($limit);

        $patients = $query->get()->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->user->name ?? null,
                'document_number' => $p->user->document_number ?? null,
                'document_type' => $p->user->document_type ?? null,
                'phone' => $p->user->phone ?? null,
            ];
        });

        return response()->json([
            'data' => $patients,
        ]);
    }
}
