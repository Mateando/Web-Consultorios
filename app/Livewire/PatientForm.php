<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Validation\Rule;

class PatientForm extends Component
{
    public $patientId = null;
    public $step = 1;

    // Campos básicos
    public $name = '';
    public $email = '';
    public $document_type = '';
    public $document_number = '';
    public $phone = '';

    // Dirección
    public $country = '';
    public $province = '';
    public $city = '';
    public $address = '';

    // Clínicos
    public $patient_type = '';
    public $insurance_provider = '';
    public $allergies = '';
    public $notes = '';

    public $show = false;

    protected function rules()
    {
        return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($this->patientUserId())],
            'document_type' => ['nullable','string','max:50'],
            'document_number' => ['nullable','string','max:50'],
            'phone' => ['nullable','string','max:50'],
            'country' => ['nullable','string','max:100'],
            'province' => ['nullable','string','max:100'],
            'city' => ['nullable','string','max:100'],
            'address' => ['nullable','string','max:255'],
            'patient_type' => ['nullable','string','max:100'],
            'insurance_provider' => ['nullable','string','max:150'],
            'allergies' => ['nullable','string','max:255'],
            'notes' => ['nullable','string','max:500'],
        ];
    }

    public function patientUserId()
    {
        if(!$this->patientId) return null;
        $patient = Patient::find($this->patientId);
        return $patient?->user_id;
    }

    public function open($patientId = null)
    {
        $this->resetValidation();
        $this->resetExcept('show');
        $this->show = true;
        $this->step = 1;
        $this->patientId = $patientId;
        if($patientId){
            $patient = Patient::with('user')->find($patientId);
            if($patient && $patient->user){
                $u = $patient->user;
                $this->name = $u->name;
                $this->email = $u->email;
                $this->document_type = $u->document_type;
                $this->document_number = $u->document_number;
                $this->phone = $u->phone;
                $this->country = $u->country;
                $this->province = $u->province;
                $this->city = $u->city;
                $this->address = $u->address;
                $this->patient_type = $patient->patient_type;
                $this->insurance_provider = $patient->insurance_provider;
                $this->allergies = $patient->allergies;
                $this->notes = $patient->notes;
            }
        }
    }

    public function close()
    {
        $this->show = false;
    }

    public function nextStep()
    {
        $this->validateCurrentStep();
        if($this->step < 5){
            $this->step++;
        }
    }

    public function prevStep()
    {
        if($this->step > 1){
            $this->step--;
        }
    }

    protected function validateCurrentStep()
    {
        $rules = $this->rules();
        $stepFields = [
            1 => ['name','email','document_type','document_number'],
            2 => ['phone','country','province','city'],
            3 => ['address'],
            4 => ['patient_type','insurance_provider','allergies'],
            5 => ['notes'],
        ];
        $this->validate(array_intersect_key($rules, array_flip($stepFields[$this->step])));
    }

    public function save()
    {
        $this->validate();

        if($this->patientId){
            $patient = Patient::with('user')->find($this->patientId);
            if(!$patient) return;
            $patient->user->update([
                'name'=>$this->name,
                'email'=>$this->email,
                'document_type'=>$this->document_type,
                'document_number'=>$this->document_number,
                'phone'=>$this->phone,
                'country'=>$this->country,
                'province'=>$this->province,
                'city'=>$this->city,
                'address'=>$this->address,
            ]);
            $patient->update([
                'patient_type'=>$this->patient_type,
                'insurance_provider'=>$this->insurance_provider,
                'allergies'=>$this->allergies,
                'notes'=>$this->notes,
            ]);
            $wasEditing = true;
        } else {
            $user = User::create([
                'name'=>$this->name,
                'email'=>$this->email,
                'password'=>bcrypt('password'), // placeholder
                'document_type'=>$this->document_type,
                'document_number'=>$this->document_number,
                'phone'=>$this->phone,
                'country'=>$this->country,
                'province'=>$this->province,
                'city'=>$this->city,
                'address'=>$this->address,
            ]);
            $patient = Patient::create([
                'user_id'=>$user->id,
                'patient_type'=>$this->patient_type,
                'insurance_provider'=>$this->insurance_provider,
                'allergies'=>$this->allergies,
                'notes'=>$this->notes,
            ]);
            $wasEditing = false;
        }

        $this->dispatch('patientSaved');
        $this->dispatch('notify', type:'success', message: $wasEditing ? 'Paciente actualizado' : 'Paciente creado');
        $this->close();
    }

    public function render()
    {
        return view('livewire.patient-form');
    }
}
