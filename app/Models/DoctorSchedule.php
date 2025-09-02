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

        $duration = (int) ($this->appointment_duration ?? 0);
        if ($duration <= 0) {
            return $slots;
        }

        // Intentar parsear start_time/end_time de forma segura con Carbon.
        // Aceptamos que las propiedades puedan ser strings ("08:00"/"08:00:00") o instancias Carbon
        try {
            if ($this->start_time instanceof \Carbon\Carbon) {
                $start = $this->start_time->copy();
            } else {
                // Permitimos formatos H:i:s y H:i
                $start = \Carbon\Carbon::parse((string) $this->start_time);
            }

            if ($this->end_time instanceof \Carbon\Carbon) {
                $end = $this->end_time->copy();
            } else {
                $end = \Carbon\Carbon::parse((string) $this->end_time);
            }
        } catch (\Throwable $e) {
            // Si no se puede parsear, devolver array vacío para evitar errores
            return $slots;
        }

        // Normalizar ambas fechas al mismo día para comparar solo horas (evita problemas de zona/fecha)
        $start->setDate(1970, 1, 1);
        $end->setDate(1970, 1, 1);

        // Generar slots avanzando por minutos de duración
        $safety = 0;
        while ($start->lt($end)) {
            $slots[] = $start->format('H:i');
            $start = $start->copy()->addMinutes($duration);
            // Safety guard to avoid infinite loops
            if (++$safety > 1000) break;
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
