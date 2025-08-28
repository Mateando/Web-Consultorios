<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    'patient_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'insurance_provider',
        'insurance_number',
        'allergies',
        'medical_conditions',
        'medications',
        'blood_type',
        'height',
        'weight',
    'observations',
    'extra_observations',
    ];

    protected function casts(): array
    {
        return [
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    // Accesorios
    public function getFullNameAttribute()
    {
        return $this->user->name;
    }

    public function getAgeAttribute()
    {
        return $this->user->birth_date ? $this->user->birth_date->age : null;
    }

    public function getBmiAttribute()
    {
        if ($this->height && $this->weight) {
            $heightInMeters = $this->height / 100;
            return round($this->weight / ($heightInMeters * $heightInMeters), 2);
        }
        return null;
    }
}
