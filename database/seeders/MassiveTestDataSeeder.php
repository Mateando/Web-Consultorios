<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use App\Models\DoctorSchedule;
use App\Models\Appointment;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class MassiveTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Evitar re-ejecutar si ya hay datos
        if (Doctor::count() >= 20 && Patient::count() >= 200 && Appointment::count() >= 800) {
            $this->command->info('Ya existen suficientes datos de prueba. Seeder omitido.');
            return;
        }

        // Crear roles necesarios
    Role::firstOrCreate(['name' => 'medico']);
    Role::firstOrCreate(['name' => 'paciente']);
    Role::firstOrCreate(['name' => 'recepcionista']);

        // Crear especialidades base
        $specialtyNames = [
            'Medicina General', 'Pediatría', 'Cardiología', 'Neurología', 'Traumatología', 'Dermatología', 'Ginecología', 'Oftalmología'
        ];
        $specialties = [];
        foreach ($specialtyNames as $name) {
            $specialties[] = Specialty::firstOrCreate(['name' => $name]);
        }

        $this->command->info('Especialidades creadas/aseguradas: ' . count($specialties));

        // Crear 20 doctores, cada uno con una sola especialidad
        $doctors = collect();
        for ($i = 1; $i <= 20; $i++) {
            $email = "doctor{$i}@example.test";
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => 'Dr. Test ' . $i, 'password' => bcrypt('password')]
            );
            // Asignar rol si no lo tiene
            if (!$user->hasRole('medico')) $user->assignRole('medico');

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                ['license_number' => 'LIC-' . strtoupper(substr(md5($user->email), 0, 8)), 'consultation_fee' => rand(1000, 5000) / 10, 'is_available' => true]
            );

            // Asignar una sola especialidad aleatoria
            $specialty = $specialties[array_rand($specialties)];
            $doctor->specialties()->sync([$specialty->id]);

            $doctors->push($doctor);
        }

        $this->command->info('Doctores creados: ' . $doctors->count());

        // Asegurar tipos de estudio base ANTES de generar citas y asociarlos a doctores
        if (class_exists(\App\Models\StudyType::class)) {
            $defaultStudyTypes = [
                ['name' => 'Ecografía Abdominal', 'description' => 'Ecografía Abdominal', 'cost' => 15000, 'is_active' => true],
                ['name' => 'Radiografía Tórax', 'description' => 'Radiografía Tórax', 'cost' => 12000, 'is_active' => true],
                ['name' => 'Electrocardiograma', 'description' => 'Electrocardiograma', 'cost' => 8000, 'is_active' => true],
                ['name' => 'Ergometría', 'description' => 'Ergometría', 'cost' => 25000, 'is_active' => true],
                ['name' => 'Laboratorio Básico', 'description' => 'Laboratorio Básico', 'cost' => 6000, 'is_active' => true],
            ];
            foreach ($defaultStudyTypes as $st) {
                \App\Models\StudyType::firstOrCreate(['name' => $st['name']], $st);
            }
            $allStudyTypes = \App\Models\StudyType::all();
            // Asociar 2-3 tipos al azar a cada doctor si aún no tiene
            foreach ($doctors as $doc) {
                if ($doc->studyTypes()->count() === 0 && $allStudyTypes->count() > 0) {
                    $attach = $allStudyTypes->shuffle()->take(min(max(2, rand(2,3)), $allStudyTypes->count()))->pluck('id')->all();
                    $doc->studyTypes()->attach($attach);
                }
            }
            $this->command->info('Tipos de estudio asegurados y asociados a doctores. Total tipos: ' . $allStudyTypes->count());
        }

        // Crear horarios para cada doctor (lunes a viernes, mañana y tarde)
        $weekdays = ['monday','tuesday','wednesday','thursday','friday'];
        foreach ($doctors as $doctor) {
            foreach ($weekdays as $day) {
                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'specialty_id' => $doctor->specialties()->first()->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00',
                    'end_time' => '12:00',
                    'appointment_duration' => 30,
                    'is_active' => true,
                ]);

                DoctorSchedule::create([
                    'doctor_id' => $doctor->id,
                    'specialty_id' => $doctor->specialties()->first()->id,
                    'day_of_week' => $day,
                    'start_time' => '14:00',
                    'end_time' => '18:00',
                    'appointment_duration' => 30,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Horarios creados para los doctores.');

        // Crear 220 pacientes
        $patients = collect();
        for ($i = 1; $i <= 220; $i++) {
            $email = "patient{$i}@example.test";
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Paciente Test ' . $i,
                    'password' => bcrypt('password'),
                    'document_type' => 'cedula',
                    'document_number' => str_pad((string)(40000000 + $i), 8, '0', STR_PAD_LEFT),
                    'phone' => '+54911'.str_pad((string)(100000 + $i), 6, '0', STR_PAD_LEFT),
                    'address' => 'Calle Ficticia '. $i,
                ]
            );
            if (!$user->hasRole('paciente')) $user->assignRole('paciente');
            $patient = Patient::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'emergency_contact_name' => 'Contacto '.$i,
                    'emergency_contact_phone' => '+54911'.str_pad((string)(200000 + $i), 6, '0', STR_PAD_LEFT),
                    'insurance_number' => 'INS'.str_pad((string)$i, 6, '0', STR_PAD_LEFT)
                ]
            );
            $patients->push($patient);
        }

        $this->command->info('Pacientes creados: ' . $patients->count());

        // Preparar fechas por día de la semana en rango -30 a +60
        $datesByWeekday = [];
        $startRange = Carbon::now()->subDays(30)->startOfDay();
        $endRange = Carbon::now()->addDays(60)->endOfDay();
        $cursor = $startRange->copy();
        while ($cursor <= $endRange) {
            $datesByWeekday[strtolower($cursor->format('l'))][] = $cursor->copy();
            $cursor->addDay();
        }

        // Crear ~800 citas respetando horarios.
        // Distribución por especialidad: proporcional al número de doctores por especialidad.
        // Distribución temporal: 50% futuro (next 60 days), 40% pasado (last 30 days), 10% hoy.
        $target = 800;
        $doctorsWithSchedules = Doctor::with(['schedules' => function($q){ $q->where('is_active', true); }, 'specialties'])->get();

        $specialtyGroups = $doctorsWithSchedules->groupBy(function ($doc) {
            return optional($doc->specialties->first())->id;
        });

        // Normalizar (remover keys null)
        $specialtyGroups = $specialtyGroups->filter(function ($g, $k) { return $k !== null; });

        $totalDoctors = $specialtyGroups->sum(function ($group) { return $group->count(); });
        if ($totalDoctors == 0) {
            $this->command->info('No hay doctores con especialidades para crear citas.');
            return;
        }

        // Calcular objetivos por especialidad
        $targetsBySpecialty = [];
        foreach ($specialtyGroups as $specialtyId => $group) {
            $count = $group->count();
            $targetsBySpecialty[$specialtyId] = max(1, (int) round(($count / $totalDoctors) * $target));
        }

        // Ajustar para sumar exactamente target
        $sumTargets = array_sum($targetsBySpecialty);
        if ($sumTargets !== $target) {
            $diff = $target - $sumTargets;
            // Ajustar la última especialidad
            $keys = array_keys($targetsBySpecialty);
            $last = end($keys);
            $targetsBySpecialty[$last] += $diff;
        }

        $statuses = ['programada','confirmada','completada','cancelada'];
        $reasons = ['Consulta general','Control','Dolor','Seguimiento','Chequeo'];

        // Pools de fechas: pasado, hoy, futuro
        $pastDates = [];
        $todayDates = [];
        $futureDates = [];
        foreach ($datesByWeekday as $weekday => $dates) {
            foreach ($dates as $d) {
                if ($d->isToday()) $todayDates[] = $d->copy();
                elseif ($d->isPast()) $pastDates[] = $d->copy();
                else $futureDates[] = $d->copy();
            }
        }

        $created = 0;
        $attempts = 0;
        $createdBySpecialty = [];

        foreach ($targetsBySpecialty as $specialtyId => $specTarget) {
            $createdBySpecialty[$specialtyId] = 0;
            $doctorsOfSpec = $specialtyGroups[$specialtyId];
            $specAttempts = 0;

            while ($createdBySpecialty[$specialtyId] < $specTarget && $specAttempts < $specTarget * 20) {
                $specAttempts++; $attempts++;

                $doctor = $doctorsOfSpec->random();
                if ($doctor->schedules->isEmpty()) continue;

                // Seleccionar random schedule de este doctor
                $schedule = $doctor->schedules->random();
                $day = $schedule->day_of_week;
                $possibleDates = $datesByWeekday[$day] ?? [];
                if (empty($possibleDates)) continue;

                // Elegir tipo de fecha según probabilidad: 40% pasado, 10% hoy, 50% futuro
                $r = rand(1,100);
                if ($r <= 40 && !empty($pastDates)) {
                    $date = collect($pastDates)->random()->copy();
                } elseif ($r <= 50 && !empty($todayDates)) {
                    $date = collect($todayDates)->random()->copy();
                } else {
                    if (empty($futureDates)) { $date = collect($possibleDates)->random()->copy(); }
                    else $date = collect($futureDates)->random()->copy();
                }

                // Asegurar que la fecha elegida coincida con el día de semana del schedule
                if (strtolower($date->format('l')) !== $day) {
                    // Buscar una fecha del día correcto en el pool adecuado
                    $pool = $date->isPast() ? $pastDates : ($date->isToday() ? $todayDates : $futureDates);
                    $matches = array_filter($pool, function ($d) use ($day) {
                        return strtolower($d->format('l')) === $day;
                    });
                    if (empty($matches)) continue;
                    $date = collect($matches)->random()->copy();
                }

                $slots = $schedule->getAvailableTimeSlots();
                if (empty($slots)) continue;
                $time = $slots[array_rand($slots)];
                [$hour, $minute] = explode(':', $time);
                $appointmentDate = $date->copy()->setHour((int)$hour)->setMinute((int)$minute)->setSecond(0);

                // Evitar conflictos
                $conflict = Appointment::where('doctor_id', $doctor->id)
                    ->where('appointment_date', $appointmentDate)
                    ->exists();
                if ($conflict) continue;

                $patient = $patients->random();

                $studyTypeId = null;
                if (class_exists(\App\Models\StudyType::class) && rand(1,100) <= 50) {
                    // Elegir solo entre los estudios que el doctor puede realizar para pasar validación
                    $doctorStudyIds = $doctor->studyTypes()->pluck('study_types.id')->all();
                    if (!empty($doctorStudyIds)) {
                        $studyTypeId = $doctorStudyIds[array_rand($doctorStudyIds)];
                    }
                }
                Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'specialty_id' => $specialtyId,
                    'study_type_id' => $studyTypeId,
                    'appointment_date' => $appointmentDate,
                    'duration' => $schedule->appointment_duration ?? 30,
                    'status' => $appointmentDate->isToday() ? 'programada' : $statuses[array_rand($statuses)],
                    'reason' => $reasons[array_rand($reasons)],
                    'created_by' => User::role('recepcionista')->first()->id ?? null,
                ]);

                $createdBySpecialty[$specialtyId]++;
                $created++;
            }
        }

        $this->command->info('Citas creadas totales: ' . $created . ' (intentos: ' . $attempts . ')');
        foreach ($createdBySpecialty as $sid => $cnt) {
            $name = optional(Specialty::find($sid))->name ?? 'N/A';
            $this->command->info("  - {$name} ({$sid}): {$cnt}");
        }
    }
}
