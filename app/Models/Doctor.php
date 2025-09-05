<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'education',
        'years_experience',
        'bio',
        'schedule',
        'consultation_fee',
        'is_available',
        'availability_schedule',
        'phone',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'consultation_fee' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class);
    }

    // Mantener compatibility con código existente
    public function specialty()
    {
        return $this->specialties()->first();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function studyTypes()
    {
        return $this->belongsToMany(\App\Models\StudyType::class, 'doctor_study_type');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function insuranceProviders()
    {
        return $this->belongsToMany(\App\Models\InsuranceProvider::class, 'doctor_insurance_provider');
    }

    // Estudios (tipos) que puede realizar el doctor filtrados opcionalmente por specialty
    public function studyTypesForSpecialty($specialtyId = null)
    {
        $query = $this->studyTypes();
    if ($specialtyId && Schema::hasColumn('study_types', 'specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }
        return $query;
    }

    // Obtener horarios para una especialidad específica
    public function schedulesForSpecialty($specialtyId)
    {
        return $this->schedules()->where('specialty_id', $specialtyId);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        })->whereHas('specialties', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithSpecialty($query, $specialtyId)
    {
        return $query->whereHas('specialties', function ($q) use ($specialtyId) {
            $q->where('specialties.id', $specialtyId);
        });
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    // Accesorios
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }
}
