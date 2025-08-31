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
    public $filterSpecialty;
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

        if ($this->filterSpecialty) {
            $query->whereHas('doctor.specialties', function ($q) {
                $q->where('specialties.id', $this->filterSpecialty);
            });
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

        // Preparar listas dependientes para los filtros (consultas explícitas)
        // Especialidades: si hay doctor seleccionado, solo las del doctor; si no, todas activas
        if ($this->filterDoctor) {
            $doc = Doctor::find($this->filterDoctor);
            if ($doc) {
                $specialtiesList = $doc->specialties()->where('is_active', true)->get();
                // Si la specialty seleccionada no pertenece al doctor, resetearla
                if ($this->filterSpecialty && !$specialtiesList->contains('id', $this->filterSpecialty)) {
                    $this->filterSpecialty = null;
                }
            } else {
                $specialtiesList = Specialty::active()->get();
            }
        } else {
            $specialtiesList = Specialty::active()->get();
        }

        // Doctores: si hay specialty seleccionada, solo doctores con esa especialidad; si no, todos disponibles
        if ($this->filterSpecialty) {
            $doctorsList = Doctor::with(['user', 'specialties'])
                ->available()
                ->whereHas('specialties', function ($q) {
                    $q->where('specialties.id', $this->filterSpecialty)->where('specialties.is_active', true);
                })->get();
            // Si el doctor seleccionado no pertenece a la specialty, resetearlo
            if ($this->filterDoctor) {
                $doctorIds = $doctorsList->pluck('id')->all();
                if (!in_array($this->filterDoctor, $doctorIds)) {
                    $this->filterDoctor = null;
                }
            }
        } else {
            $doctorsList = Doctor::with(['user', 'specialties'])->available()->get();
        }

        return view('livewire.appointment-manager', [
            'appointments' => $appointments,
            'patients' => Patient::with('user')->get(),
            'doctors' => $doctorsList,
            'specialties' => $specialtiesList,
            'availableDoctors' => $this->getAvailableDoctors(),
        ]);
    }

    public function updatedFilterDoctor($value)
    {
        // Al cambiar doctor en los filtros, resetear paginación y limitar la especialidad
        $this->resetPage();

        if (!$value) {
            // si se deselecciona doctor, recargar especialidades completas
            $this->filterSpecialty = null;
            return;
        }

        $doc = Doctor::with('specialties')->find($value);
        if ($doc) {
            $specialtyIds = $doc->specialties->pluck('id')->all();
            if ($this->filterSpecialty && !in_array($this->filterSpecialty, $specialtyIds)) {
                $this->filterSpecialty = null;
            }
        } else {
            $this->filterSpecialty = null;
        }
    }

    public function updatedFilterSpecialty($value)
    {
        // Al cambiar especialidad en los filtros, resetear paginación y resetear doctor si no pertenece
        $this->resetPage();

        if (!$value) {
            $this->filterDoctor = null;
            return;
        }

        $doctorIds = Doctor::whereHas('specialties', function ($q) use ($value) {
            $q->where('specialties.id', $value);
        })->available()->pluck('id')->all();

        if ($this->filterDoctor && !in_array($this->filterDoctor, $doctorIds)) {
            $this->filterDoctor = null;
        }
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
