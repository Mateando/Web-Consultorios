<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'specialty_id',
        'appointment_date',
        'duration',
        'status',
        'reason',
        'notes',
        'symptoms',
        'diagnosis',
        'treatment',
        'prescription',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'appointment_date' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // Validación antes de crear una cita
        static::creating(function ($appointment) {
            self::validateAppointmentTime($appointment);
        });

        // Validación antes de actualizar una cita
        static::updating(function ($appointment) {
            if ($appointment->isDirty(['appointment_date', 'doctor_id'])) {
                self::validateAppointmentTime($appointment);
            }
        });
    }

    /**
     * Validar que la cita esté dentro del horario del doctor
     */
    protected static function validateAppointmentTime($appointment)
    {
        if (!$appointment->doctor_id || !$appointment->appointment_date) {
            return;
        }

        $doctor = Doctor::find($appointment->doctor_id);
        if (!$doctor) {
            throw new \InvalidArgumentException('Doctor no válido.');
        }

        $appointmentDate = Carbon::parse($appointment->appointment_date);
        $dayOfWeek = strtolower($appointmentDate->format('l'));
        $timeSlot = $appointmentDate->format('H:i');

        // Verificar que el doctor tenga horario ese día
        $schedule = $doctor->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            $dayInSpanish = self::translateDayToSpanish($dayOfWeek);
            throw new \InvalidArgumentException("El doctor no atiende los {$dayInSpanish}.");
        }

        // Verificar que la hora esté dentro del horario
        $startTime = Carbon::parse($schedule->start_time)->format('H:i');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i');

        if ($timeSlot < $startTime || $timeSlot >= $endTime) {
            throw new \InvalidArgumentException("La hora {$timeSlot} está fuera del horario de atención ({$startTime} - {$endTime}).");
        }

        // Verificar que sea un slot válido según la duración de citas
        $availableSlots = $schedule->getAvailableTimeSlots();
        if (!in_array($timeSlot, $availableSlots)) {
            throw new \InvalidArgumentException("La hora {$timeSlot} no es un horario válido. Las citas son cada {$schedule->appointment_duration} minutos.");
        }

        // Verificar que no haya conflictos con otras citas
        $conflictingAppointment = self::where('doctor_id', $appointment->doctor_id)
            ->where('appointment_date', $appointmentDate)
            ->whereNotIn('status', ['cancelada'])
            ->when($appointment->exists, function ($query) use ($appointment) {
                return $query->where('id', '!=', $appointment->id);
            })
            ->first();

        if ($conflictingAppointment) {
            throw new \InvalidArgumentException("Ya existe una cita programada para esta fecha y hora.");
        }
    }

    /**
     * Traducir días al español
     */
    protected static function translateDayToSpanish($day)
    {
        return match($day) {
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miércoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sábados',
            'sunday' => 'domingos',
            default => $day,
        };
    }

    // Relaciones
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', Carbon::today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>', Carbon::now());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    // Accesorios
    public function getEndTimeAttribute()
    {
        return $this->appointment_date->addMinutes($this->duration);
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completada';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelada';
    }
}
