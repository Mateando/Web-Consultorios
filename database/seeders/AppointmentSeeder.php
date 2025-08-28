<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\User;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Evitar duplicar datos si ya existen citas
        if (Appointment::count() >= 50) {
            return; // Asumimos que ya se sembraron suficientes
        }

        $patients = Patient::all();
        $doctors = Doctor::with(['schedules' => function($q){ $q->where('is_active', true); }])->get();
        $receptionistId = User::role('recepcionista')->first()->id;

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            return; // No hay datos base suficientes
        }

        $statuses = ['programada', 'confirmada', 'completada', 'cancelada'];
        $reasons = [
            'Consulta general',
            'Control rutinario',
            'Dolor de cabeza',
            'Revisión médica',
            'Seguimiento de tratamiento',
            'Chequeo anual',
            'Consulta por síntomas',
            'Medicina preventiva',
        ];

        // Pre-calcular rango de fechas (-30 a +30) agrupadas por día de semana
        $datesByWeekday = [];
        $startRange = Carbon::now()->copy()->subDays(30)->startOfDay();
        $endRange = Carbon::now()->copy()->addDays(30)->endOfDay();
        $cursor = $startRange->copy();
        while ($cursor <= $endRange) {
            $datesByWeekday[strtolower($cursor->format('l'))][] = $cursor->copy();
            $cursor->addDay();
        }

        $created = 0;
        $attempts = 0;
        $target = 50;

        while ($created < $target && $attempts < $target * 10) {
            $attempts++;
            $doctor = $doctors->random();
            if ($doctor->schedules->isEmpty()) continue;

            // Seleccionar un horario activo aleatorio
            /** @var DoctorSchedule $schedule */
            $schedule = $doctor->schedules->random();
            $day = $schedule->day_of_week; // monday, tuesday etc
            $possibleDates = $datesByWeekday[$day] ?? [];
            if (empty($possibleDates)) continue;
            /** @var Carbon $date */
            $date = collect($possibleDates)->random()->copy();

            // Seleccionar un slot válido de ese horario
            $slots = $schedule->getAvailableTimeSlots();
            if (empty($slots)) continue;
            $time = collect($slots)->random();
            [$hour, $minute] = explode(':', $time);
            $appointmentDate = $date->copy()->setHour((int)$hour)->setMinute((int)$minute)->setSecond(0);

            // Evitar conflicto manualmente
            $conflict = Appointment::where('doctor_id', $doctor->id)
                ->where('appointment_date', $appointmentDate)->exists();
            if ($conflict) continue;

            $status = collect($statuses)->random();
            if ($appointmentDate->isPast()) {
                $status = collect(['completada', 'cancelada', 'no_asistio'])->random();
            }

            // Tomar primera especialidad del doctor si existe
            $specialtyId = optional($doctor->specialties->first())->id;

            Appointment::create([
                'patient_id' => $patients->random()->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $specialtyId,
                'appointment_date' => $appointmentDate,
                'duration' => $schedule->appointment_duration ?? 30,
                'status' => $status,
                'reason' => collect($reasons)->random(),
                'notes' => $status === 'completada' ? 'Consulta completada satisfactoriamente' : null,
                'symptoms' => $status === 'completada' ? 'Síntomas evaluados' : null,
                'diagnosis' => $status === 'completada' ? 'Diagnóstico establecido' : null,
                'treatment' => $status === 'completada' ? 'Tratamiento prescrito' : null,
                'prescription' => $status === 'completada' ? 'Medicamentos recetados según necesidad' : null,
                'created_by' => $receptionistId,
            ]);
            $created++;
        }
    }
}
