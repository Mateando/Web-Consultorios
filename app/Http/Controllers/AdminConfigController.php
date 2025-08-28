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
}
