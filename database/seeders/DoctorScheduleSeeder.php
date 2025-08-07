<?php

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\DoctorSchedule;

class DoctorScheduleSeeder extends Seeder
{
    public function run()
    {
        // Obtener algunos doctores y especialidades
        $doctors = Doctor::with('specialties')->take(3)->get();
        
        foreach ($doctors as $doctor) {
            foreach ($doctor->specialties as $specialty) {
                // Horarios de lunes a viernes para cada especialidad
                $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                
                foreach ($weekdays as $day) {
                    // Horario de mañana: 8:00 - 12:00
                    DoctorSchedule::create([
                        'doctor_id' => $doctor->id,
                        'specialty_id' => $specialty->id,
                        'day_of_week' => $day,
                        'start_time' => '08:00',
                        'end_time' => '12:00',
                        'appointment_duration' => 30,
                        'is_active' => true,
                    ]);
                    
                    // Horario de tarde: 14:00 - 18:00 (solo para pediatría y cardiología)
                    if (in_array($specialty->name, ['Pediatría', 'Cardiología'])) {
                        DoctorSchedule::create([
                            'doctor_id' => $doctor->id,
                            'specialty_id' => $specialty->id,
                            'day_of_week' => $day,
                            'start_time' => '14:00',
                            'end_time' => '18:00',
                            'appointment_duration' => 30,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        }
    }
}
