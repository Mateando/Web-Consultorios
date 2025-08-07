<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\DoctorSchedule as DoctorScheduleModel;
use Livewire\WithPagination;

class DoctorSchedule extends Component
{
    use WithPagination;

    public $doctorId;
    public $specialtyId;
    public $dayOfWeek;
    public $startTime;
    public $endTime;
    public $appointmentDuration = 30;
    public $isActive = true;
    public $editingScheduleId = null;

    public $showForm = false;
    public $selectedDoctor = null;

    protected $rules = [
        'doctorId' => 'required|exists:doctors,id',
        'specialtyId' => 'required|exists:specialties,id',
        'dayOfWeek' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
        'startTime' => 'required|date_format:H:i',
        'endTime' => 'required|date_format:H:i|after:startTime',
        'appointmentDuration' => 'required|integer|min:15|max:120',
        'isActive' => 'boolean',
    ];

    protected $messages = [
        'doctorId.required' => 'Debe seleccionar un doctor.',
        'doctorId.exists' => 'El doctor seleccionado no existe.',
        'specialtyId.required' => 'Debe seleccionar una especialidad.',
        'specialtyId.exists' => 'La especialidad seleccionada no existe.',
        'dayOfWeek.required' => 'Debe seleccionar un día de la semana.',
        'dayOfWeek.in' => 'El día seleccionado no es válido.',
        'startTime.required' => 'Debe especificar la hora de inicio.',
        'startTime.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
        'endTime.required' => 'Debe especificar la hora de fin.',
        'endTime.date_format' => 'La hora de fin debe tener el formato HH:MM.',
        'endTime.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        'appointmentDuration.required' => 'Debe especificar la duración de las citas.',
        'appointmentDuration.integer' => 'La duración debe ser un número entero.',
        'appointmentDuration.min' => 'La duración mínima es de 15 minutos.',
        'appointmentDuration.max' => 'La duración máxima es de 120 minutos.',
    ];

    public function mount($doctorId = null)
    {
        if ($doctorId) {
            $this->doctorId = $doctorId;
            $this->selectedDoctor = Doctor::find($doctorId);
        }
    }

    public function updatedDoctorId()
    {
        $this->selectedDoctor = Doctor::with('specialties')->find($this->doctorId);
        $this->specialtyId = null;
    }

    public function openForm()
    {
        $this->reset(['specialtyId', 'dayOfWeek', 'startTime', 'endTime', 'appointmentDuration', 'isActive', 'editingScheduleId']);
        $this->appointmentDuration = 30;
        $this->isActive = true;
        $this->showForm = true;
    }

    public function editSchedule($scheduleId)
    {
        $schedule = DoctorScheduleModel::find($scheduleId);
        
        if ($schedule) {
            $this->editingScheduleId = $scheduleId;
            $this->doctorId = $schedule->doctor_id;
            $this->specialtyId = $schedule->specialty_id;
            $this->dayOfWeek = $schedule->day_of_week;
            $this->startTime = $schedule->start_time;
            $this->endTime = $schedule->end_time;
            $this->appointmentDuration = $schedule->appointment_duration;
            $this->isActive = $schedule->is_active;
            $this->selectedDoctor = Doctor::with('specialties')->find($this->doctorId);
            $this->showForm = true;
        }
    }

    public function save()
    {
        $this->validate();

        // Validar que el doctor tenga la especialidad seleccionada
        $doctor = Doctor::with('specialties')->find($this->doctorId);
        if (!$doctor->specialties->contains($this->specialtyId)) {
            $this->addError('specialtyId', 'El doctor no tiene asignada esta especialidad.');
            return;
        }

        // Verificar conflictos de horario
        $hasConflict = DoctorScheduleModel::hasScheduleConflict(
            $this->doctorId,
            $this->dayOfWeek,
            $this->startTime,
            $this->endTime,
            $this->editingScheduleId
        );

        if ($hasConflict) {
            $conflictingSchedules = DoctorScheduleModel::getConflictingSchedules(
                $this->doctorId,
                $this->dayOfWeek,
                $this->startTime,
                $this->endTime,
                $this->editingScheduleId
            );

            $conflicts = $conflictingSchedules->map(function($schedule) {
                return $schedule->specialty->name . ' (' . $schedule->start_time . ' - ' . $schedule->end_time . ')';
            })->implode(', ');

            $this->addError('startTime', "El horario se solapa con: {$conflicts}");
            return;
        }

        try {
            if ($this->editingScheduleId) {
                $schedule = DoctorScheduleModel::find($this->editingScheduleId);
                $schedule->update([
                    'doctor_id' => $this->doctorId,
                    'specialty_id' => $this->specialtyId,
                    'day_of_week' => $this->dayOfWeek,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'appointment_duration' => $this->appointmentDuration,
                    'is_active' => $this->isActive,
                ]);
                session()->flash('message', 'Horario actualizado exitosamente.');
            } else {
                DoctorScheduleModel::create([
                    'doctor_id' => $this->doctorId,
                    'specialty_id' => $this->specialtyId,
                    'day_of_week' => $this->dayOfWeek,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                    'appointment_duration' => $this->appointmentDuration,
                    'is_active' => $this->isActive,
                ]);
                session()->flash('message', 'Horario creado exitosamente.');
            }

            $this->closeForm();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar el horario: ' . $e->getMessage());
        }
    }

    public function deleteSchedule($scheduleId)
    {
        try {
            DoctorScheduleModel::find($scheduleId)->delete();
            session()->flash('message', 'Horario eliminado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar el horario: ' . $e->getMessage());
        }
    }

    public function toggleStatus($scheduleId)
    {
        try {
            $schedule = DoctorScheduleModel::find($scheduleId);
            $schedule->update(['is_active' => !$schedule->is_active]);
            session()->flash('message', 'Estado del horario actualizado.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->reset(['specialtyId', 'dayOfWeek', 'startTime', 'endTime', 'appointmentDuration', 'isActive', 'editingScheduleId']);
        $this->resetErrorBag();
    }

    public function render()
    {
        $doctors = Doctor::with('specialties', 'user')->get();
        $specialties = Specialty::where('is_active', true)->get();
        
        $schedules = DoctorScheduleModel::with(['doctor.user', 'specialty'])
            ->when($this->doctorId, function($query) {
                $query->where('doctor_id', $this->doctorId);
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(10);

        $daysOfWeek = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        return view('livewire.doctor-schedule', compact('doctors', 'specialties', 'schedules', 'daysOfWeek'));
    }
}
