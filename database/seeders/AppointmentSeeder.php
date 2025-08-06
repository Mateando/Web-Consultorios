<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $doctors = Doctor::all();
        $receptionistId = User::role('recepcionista')->first()->id;
        
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

        // Crear citas para los próximos 30 días
        for ($i = 0; $i < 50; $i++) {
            $appointmentDate = Carbon::now()
                ->addDays(rand(-30, 30))
                ->setHour(rand(8, 16))
                ->setMinute(collect([0, 30])->random())
                ->setSecond(0);

            $status = collect($statuses)->random();
            
            // Si la cita es en el pasado, puede estar completada o cancelada
            if ($appointmentDate->isPast()) {
                $status = collect(['completada', 'cancelada', 'no_asistio'])->random();
            }

            Appointment::create([
                'patient_id' => $patients->random()->id,
                'doctor_id' => $doctors->random()->id,
                'appointment_date' => $appointmentDate,
                'duration' => collect([30, 60])->random(),
                'status' => $status,
                'reason' => collect($reasons)->random(),
                'notes' => $status === 'completada' ? 'Consulta completada satisfactoriamente' : null,
                'symptoms' => $status === 'completada' ? 'Síntomas evaluados' : null,
                'diagnosis' => $status === 'completada' ? 'Diagnóstico establecido' : null,
                'treatment' => $status === 'completada' ? 'Tratamiento prescrito' : null,
                'prescription' => $status === 'completada' ? 'Medicamentos recetados según necesidad' : null,
                'created_by' => $receptionistId,
            ]);
        }
    }
}
