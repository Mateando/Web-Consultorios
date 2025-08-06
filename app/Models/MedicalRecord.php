<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'record_date',
        'chief_complaint',
        'history_present_illness',
        'physical_examination',
        'diagnosis',
        'treatment_plan',
        'prescription',
        'follow_up_instructions',
        'vital_signs',
        'lab_results',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'vital_signs' => 'array',
        ];
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

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // Scopes
    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('record_date', '>=', now()->subDays($days));
    }
}
