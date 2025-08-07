<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'specialty_id',
        'day_of_week',
        'start_time',
        'end_time',
        'appointment_duration',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    // Relación con Doctor
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // Relación con Specialty
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }

    // Scope para días activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para un día específico
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // Obtener nombre del día en español
    public function getDayNameAttribute()
    {
        $days = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        return $days[$this->day_of_week] ?? $this->day_of_week;
    }

    // Generar slots de tiempo disponibles
    public function getAvailableTimeSlots()
    {
        $slots = [];
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $duration = $this->appointment_duration * 60; // Convertir a segundos

        while ($start < $end) {
            $slots[] = date('H:i', $start);
            $start += $duration;
        }

        return $slots;
    }

    // Verificar si hay solapamiento de horarios para el mismo doctor
    public static function hasScheduleConflict($doctorId, $dayOfWeek, $startTime, $endTime, $excludeId = null)
    {
        $query = self::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                // Verificar solapamiento: nuevo horario empieza antes de que termine el existente
                // Y termina después de que empiece el existente
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Verificar conflictos para una especialidad específica
    public static function hasSpecialtyScheduleConflict($doctorId, $specialtyId, $dayOfWeek, $startTime, $endTime, $excludeId = null)
    {
        $query = self::where('doctor_id', $doctorId)
            ->where('specialty_id', $specialtyId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // Obtener horarios conflictivos
    public static function getConflictingSchedules($doctorId, $dayOfWeek, $startTime, $endTime, $excludeId = null)
    {
        $query = self::with('specialty')
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    $subQ->where('start_time', '<', $endTime)
                         ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }
}
