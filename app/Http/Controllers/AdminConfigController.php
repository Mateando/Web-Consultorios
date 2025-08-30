<?php

namespace App\Http\Controllers;

use App\Models\PatientType;
use App\Models\InsuranceProvider;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminConfigController extends Controller
{
    public function patientTypes()
    {
        return Inertia::render('Admin/Config/PatientTypes', [
            'items' => PatientType::orderBy('name')->paginate(15)
        ]);
    }

    public function storePatientType(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:patient_types,name'
        ]);
        PatientType::create($data);
        return back()->with('success','Tipo de paciente creado');
    }

    public function updatePatientType(Request $request, PatientType $patientType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:patient_types,name,' . $patientType->id
        ]);
        $patientType->update($data);
        return back()->with('success','Tipo de paciente actualizado');
    }

    public function togglePatientType(PatientType $patientType)
    {
        $patientType->update(['is_active' => !$patientType->is_active]);
        return back()->with('success','Estado actualizado');
    }

    public function insuranceProviders()
    {
        return Inertia::render('Admin/Config/InsuranceProviders', [
            'items' => InsuranceProvider::orderBy('name')->paginate(15)
        ]);
    }

    public function storeInsuranceProvider(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_providers,name'
        ]);
        InsuranceProvider::create($data);
        return back()->with('success','Proveedor creado');
    }

    public function updateInsuranceProvider(Request $request, InsuranceProvider $insuranceProvider)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:insurance_providers,name,' . $insuranceProvider->id
        ]);
        $insuranceProvider->update($data);
        return back()->with('success','Proveedor actualizado');
    }

    public function toggleInsuranceProvider(InsuranceProvider $insuranceProvider)
    {
        $insuranceProvider->update(['is_active' => !$insuranceProvider->is_active]);
        return back()->with('success','Estado actualizado');
    }

    public function countries()
    {
        return Inertia::render('Admin/Config/Countries', [
            'items' => Country::orderBy('name')->paginate(15)
        ]);
    }

    public function storeCountry(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:countries,name',
            'iso_code' => 'nullable|string|max:3'
        ]);
        Country::create($data);
        return back()->with('success','País creado');
    }

    public function updateCountry(Request $request, Country $country)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:countries,name,' . $country->id,
            'iso_code' => 'nullable|string|max:3'
        ]);
        $country->update($data);
        return back()->with('success','País actualizado');
    }

    public function toggleCountry(Country $country)
    {
        $country->update(['is_active' => !$country->is_active]);
        return back()->with('success','Estado actualizado');
    }

    public function provinces(Country $country)
    {
        return Inertia::render('Admin/Config/Provinces', [
            'country' => $country,
            'items' => $country->provinces()->orderBy('name')->paginate(30)
        ]);
    }

    public function storeProvince(Request $request, Country $country)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $country->provinces()->create($data);
        return back()->with('success','Provincia creada');
    }

    public function updateProvince(Request $request, Province $province)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $province->update($data);
        return back()->with('success','Provincia actualizada');
    }

    public function toggleProvince(Province $province)
    {
        $province->update(['is_active' => !$province->is_active]);
        return back()->with('success','Estado actualizado');
    }

    public function cities(Province $province)
    {
        return Inertia::render('Admin/Config/Cities', [
            'province' => $province->load('country'),
            'items' => $province->cities()->orderBy('name')->paginate(50)
        ]);
    }

    public function storeCity(Request $request, Province $province)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $province->cities()->create($data);
        return back()->with('success','Ciudad creada');
    }

    public function updateCity(Request $request, City $city)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255'
        ]);
        $city->update($data);
        return back()->with('success','Ciudad actualizada');
    }

    public function toggleCity(City $city)
    {
        $city->update(['is_active' => !$city->is_active]);
        return back()->with('success','Estado actualizado');
    }

    // API para selects dependientes
    public function apiCountries(Request $request)
    {
        $search = $request->get('search');
        $query = Country::where('is_active', true);
        if ($search) {
            $query->where('name','like',"%$search%");
        }
        return response()->json($query->orderBy('name')->get(['id','name']));
    }

    public function apiProvinces(Request $request, Country $country)
    {
        $search = $request->get('search');
        $query = $country->provinces()->where('is_active', true);
        if ($search) {
            $query->where('name','like',"%$search%");
        }
        return response()->json($query->orderBy('name')->get(['id','name']));
    }

    public function apiCities(Request $request, Province $province)
    {
        $search = $request->get('search');
        $query = $province->cities()->where('is_active', true);
        if ($search) {
            $query->where('name','like',"%$search%");
        }
        return response()->json($query->orderBy('name')->get(['id','name']));
    }

    // API catálogos simples
    public function apiPatientTypes(Request $request)
    {
        $search = $request->get('search');
        $query = PatientType::where('is_active', true);
        if($search){ $query->where('name','like',"%$search%"); }
        return response()->json($query->orderBy('name')->get(['id','name']));
    }
    public function apiInsuranceProviders(Request $request)
    {
        $search = $request->get('search');
        $query = InsuranceProvider::where('is_active', true);
        if($search){ $query->where('name','like',"%$search%"); }
        return response()->json($query->orderBy('name')->get(['id','name']));
    }

    // API: Appointment Reasons (select)
    public function apiAppointmentReasons(Request $request)
    {
        $query = \App\Models\AppointmentReason::where('is_active', true);
        $search = $request->get('search');
        if ($search) $query->where('name', 'like', "%$search%");
        return response()->json($query->orderBy('name')->get(['id','name']));
    }

    // API: Plantillas de ordenes activas (para selector en Reportes)
    public function apiActiveMedicalOrderTemplates(Request $request)
    {
        $items = \App\Models\MedicalOrderTemplate::where('is_active', true)->orderBy('title')->get(['id','title']);
        return response()->json($items);
    }

    // Motivos de Turnos - ABM
    public function appointmentReasons()
    {
        return Inertia::render('Admin/Config/AppointmentReasons', [
            'items' => \App\Models\AppointmentReason::orderBy('name')->paginate(20)
        ]);
    }

    public function storeAppointmentReason(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    \App\Models\AppointmentReason::create($data);
    return back()->with('success','Motivo creado');
    }

    public function updateAppointmentReason(Request $request, \App\Models\AppointmentReason $appointmentReason)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
    $appointmentReason->update($data);
    return back()->with('success','Motivo actualizado');
    }

    public function toggleAppointmentReason(\App\Models\AppointmentReason $appointmentReason)
    {
        $appointmentReason->update(['is_active' => !$appointmentReason->is_active]);
        return back()->with('success','Estado actualizado');
    }

    // API: devolver feriados (opcionalmente para un rango o una fecha)
    public function apiHolidays(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $query = \App\Models\Holiday::where('is_active', true);

        if ($from && $to) {
            // devolver feriados entre dos fechas (incluyendo recurrentes que caen en el rango)
            $query->where(function($q) use ($from, $to) {
                $q->whereBetween('date', [$from, $to])
                  ->orWhere(function($q2) use ($from, $to) {
                      $q2->where('is_recurring', true);
                      // Note: recurrentes no son filtradas por año aquí; el frontend puede mapear por mes/día
                  });
            });
        }

        $items = $query->orderBy('date')->get();
        return response()->json($items->map(function($h) { return [
            'id' => $h->id,
            'name' => $h->name,
            'date' => $h->date->toDateString(),
            'is_recurring' => (bool)$h->is_recurring,
            'notes' => $h->notes,
        ]; }));
    }

    // Feriados / Días no laborables
    public function holidays()
    {
        return Inertia::render('Admin/Config/Holidays', [
            'items' => \App\Models\Holiday::orderBy('date')->paginate(20)
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
        \App\Models\Holiday::create($data);
        return back()->with('success','Feriado creado');
    }

    public function updateHoliday(Request $request, \App\Models\Holiday $holiday)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'is_recurring' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);
        $holiday->update($data);
        return back()->with('success','Feriado actualizado');
    }

    public function toggleHoliday(\App\Models\Holiday $holiday)
    {
        $holiday->update(['is_active' => !$holiday->is_active]);
        return back()->with('success','Estado actualizado');
    }

    // Plantillas de Ordenes medicas - ABM
    public function medicalOrderTemplates()
    {
        return Inertia::render('Admin/Config/MedicalOrderTemplates', [
            'items' => \App\Models\MedicalOrderTemplate::orderBy('title')->paginate(20)
        ]);
    }

    public function storeMedicalOrderTemplate(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
        \App\Models\MedicalOrderTemplate::create($data);
        return back()->with('success','Plantilla creada');
    }

    public function updateMedicalOrderTemplate(Request $request, \App\Models\MedicalOrderTemplate $medicalOrderTemplate)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
        $medicalOrderTemplate->update($data);
        return back()->with('success','Plantilla actualizada');
    }

    public function toggleMedicalOrderTemplate(\App\Models\MedicalOrderTemplate $medicalOrderTemplate)
    {
        $medicalOrderTemplate->update(['is_active' => !$medicalOrderTemplate->is_active]);
        return back()->with('success','Estado actualizado');
    }

    // Imprimir plantilla de orden medica (vista imprimible)
    public function printMedicalOrderTemplate(\App\Models\MedicalOrderTemplate $medicalOrderTemplate)
    {
        // Solo administradores pueden imprimir desde reportes
        $user = request()->user();
        if (!$user || !$user->hasRole('administrador')) {
            abort(403);
        }

        return response()->view('admin.medical_order_template_print', [
            'item' => $medicalOrderTemplate
        ]);
    }
}
