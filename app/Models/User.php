<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
    'secondary_email',
        'password',
        'phone',
    'landline_phone',
        'address',
    'city',
    'province',
    'country',
        'birth_date',
        'gender',
        'document_type',
        'document_number',
        'is_active',
        'profile_photo_path',
        'google2fa_secret',
        'google2fa_enabled',
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->google2fa_enabled && !empty($this->google2fa_secret);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // Relaciones
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function createdAppointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    // Métodos de verificación de roles
    public function isDoctor(): bool
    {
        return $this->hasRole('medico');
    }

    public function isPatient(): bool
    {
        return $this->hasRole('paciente');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('administrador');
    }

    public function isReceptionist(): bool
    {
        return $this->hasRole('recepcionista');
    }
}
