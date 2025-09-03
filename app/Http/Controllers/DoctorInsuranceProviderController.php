<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\InsuranceProvider;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DoctorInsuranceProviderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['administrador', 'recepcionista', 'medico'])) {
            abort(403, 'No tienes permisos para ver esta página');
        }
        $filterDoctorId = $request->get('doctor_id', '');
        $search = trim($request->get('search', ''));
        $specialtyId = $request->get('specialty_id', '');

        // Query base de doctores con sus obras sociales
        $query = Doctor::with(['user','insuranceProviders', 'specialties'])
            ->leftJoin('users','users.id','=','doctors.user_id')
            ->select('doctors.*')
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
            ->orderByRaw('LOWER(users.name)');

        if ($filterDoctorId) {
            $query->where('doctors.id', $filterDoctorId);
        }

        $doctors = $query->paginate(12)->withQueryString();

        $insuranceProviders = InsuranceProvider::where('is_active', true)->get();
        $specialties = \App\Models\Specialty::where('is_active', true)->get();

        // Además devolvemos la lista completa de doctores (sin paginar) para poblar el select del filtro
        $doctorsAll = Doctor::with('user')->leftJoin('users','users.id','=','doctors.user_id')->select('doctors.*')->orderByRaw('LOWER(users.name)')->get();

        return Inertia::render('Doctors/InsuranceProviders', [
            'doctors' => $doctors,
            'doctorsAll' => $doctorsAll,
            'insuranceProviders' => $insuranceProviders,
            'specialties' => $specialties,
            'filters' => ['doctor_id' => $filterDoctorId, 'search' => $search, 'specialty_id' => $specialtyId],
        ]);
    }

    public function update(Request $request, Doctor $doctor)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['administrador', 'recepcionista'])) {
            abort(403, 'No tienes permisos para modificar este recurso');
        }

        $data = $request->validate([
            'insurance_provider_ids' => 'nullable|array',
            'insurance_provider_ids.*' => 'exists:insurance_providers,id',
        ]);

        $ids = $data['insurance_provider_ids'] ?? [];
        $doctor->insuranceProviders()->sync($ids);

        return redirect()->back()->with('success', 'Asignaciones actualizadas');
    }
}
