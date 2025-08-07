<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AppointmentManager extends Component
{
    use WithPagination;

    public $showCreateForm = false;
    public $showEditForm = false;
    public $selectedAppointment = null;

    // Formulario
    public $patient_id;
    public $doctor_id;
    public $specialty_id;
    public $appointment_date;
    public $appointment_time;
    public $duration = 30;
    public $reason;
    public $notes;
    public $status = 'programada';

    // Filtros
    public $filterDate;
    public $filterDoctor;
    public $filterStatus;
    public $filterPatient;

    protected $rules = [
        'patient_id' => 'required|exists:patients,id',
        'doctor_id' => 'required|exists:doctors,id',
        'appointment_date' => 'required|date|after_or_equal:today',
        'appointment_time' => 'required',
        'duration' => 'required|integer|min:15|max:180',
        'reason' => 'required|string|max:255',
        'notes' => 'nullable|string',
        'status' => 'required|in:programada,confirmada,completada,cancelada,no_asistio',
    ];

    public function mount()
    {
        $this->filterDate = Carbon::today()->format('Y-m-d');
    }

    public function render()
    {
        $query = Appointment::with(['patient.user', 'doctor.user', 'doctor.specialties']);

        // Aplicar filtros
        if ($this->filterDate) {
            $query->whereDate('appointment_date', $this->filterDate);
        }

        if ($this->filterDoctor) {
            $query->where('doctor_id', $this->filterDoctor);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterPatient) {
            $query->whereHas('patient.user', function ($q) {
                $q->where('name', 'like', '%' . $this->filterPatient . '%');
            });
        }

        // Si es doctor, solo mostrar sus citas
        if (Auth::user()->hasRole('medico')) {
            $query->where('doctor_id', Auth::user()->doctor->id);
        }

        // Si es paciente, solo mostrar sus citas
        if (Auth::user()->hasRole('paciente')) {
            $query->where('patient_id', Auth::user()->patient->id);
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->paginate(10);

        return view('livewire.appointment-manager', [
            'appointments' => $appointments,
            'patients' => Patient::with('user')->get(),
            'doctors' => Doctor::with(['user', 'specialties'])->available()->get(),
            'specialties' => Specialty::active()->get(),
            'availableDoctors' => $this->getAvailableDoctors(),
        ]);
    }

    public function openCreateForm()
    {
        $this->resetForm();
        $this->showCreateForm = true;
    }

    public function closeCreateForm()
    {
        $this->showCreateForm = false;
        $this->resetForm();
    }

    public function openEditForm($appointmentId)
    {
        $this->selectedAppointment = Appointment::find($appointmentId);
        $this->patient_id = $this->selectedAppointment->patient_id;
        $this->doctor_id = $this->selectedAppointment->doctor_id;
        $this->appointment_date = $this->selectedAppointment->appointment_date->format('Y-m-d');
        $this->appointment_time = $this->selectedAppointment->appointment_date->format('H:i');
        $this->duration = $this->selectedAppointment->duration;
        $this->reason = $this->selectedAppointment->reason;
        $this->notes = $this->selectedAppointment->notes;
        $this->status = $this->selectedAppointment->status;
        $this->showEditForm = true;
    }

    public function closeEditForm()
    {
        $this->showEditForm = false;
        $this->resetForm();
        $this->selectedAppointment = null;
    }

    public function save()
    {
        $this->validate();

        $appointmentDateTime = Carbon::parse($this->appointment_date . ' ' . $this->appointment_time);

        Appointment::create([
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'appointment_date' => $appointmentDateTime,
            'duration' => $this->duration,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => Auth::id(),
        ]);

        $this->closeCreateForm();
        session()->flash('message', 'Cita creada exitosamente.');
    }

    public function update()
    {
        $this->validate();

        $appointmentDateTime = Carbon::parse($this->appointment_date . ' ' . $this->appointment_time);

        $this->selectedAppointment->update([
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'appointment_date' => $appointmentDateTime,
            'duration' => $this->duration,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
        ]);

        $this->closeEditForm();
        session()->flash('message', 'Cita actualizada exitosamente.');
    }

    public function delete($appointmentId)
    {
        $appointment = Appointment::find($appointmentId);
        $appointment->delete();
        session()->flash('message', 'Cita eliminada exitosamente.');
    }

    public function updateStatus($appointmentId, $status)
    {
        $appointment = Appointment::find($appointmentId);
        $appointment->update(['status' => $status]);
        session()->flash('message', 'Estado de la cita actualizado.');
    }

    public function updatedSpecialtyId()
    {
        $this->doctor_id = null;
    }

    private function getAvailableDoctors()
    {
        if ($this->specialty_id) {
            return Doctor::with(['user', 'specialties'])
                ->whereHas('specialties', function ($q) {
                    $q->where('specialties.id', $this->specialty_id);
                })
                ->available()
                ->get();
        }

        return collect();
    }

    private function resetForm()
    {
        $this->patient_id = null;
        $this->doctor_id = null;
        $this->specialty_id = null;
        $this->appointment_date = null;
        $this->appointment_time = null;
        $this->duration = 30;
        $this->reason = null;
        $this->notes = null;
        $this->status = 'programada';
    }
}
