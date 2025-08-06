<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Specialty;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear administrador
        $admin = User::create([
            'name' => 'Administrador Sistema',
            'email' => 'admin@consultorio.com',
            'password' => Hash::make('password'),
            'phone' => '123-456-7890',
            'address' => 'Oficina Principal',
            'birth_date' => '1980-01-01',
            'gender' => 'masculino',
            'document_type' => 'cedula',
            'document_number' => 'ADM001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('administrador');

        // Crear recepcionista
        $receptionist = User::create([
            'name' => 'María González',
            'email' => 'recepcion@consultorio.com',
            'password' => Hash::make('password'),
            'phone' => '123-456-7891',
            'address' => 'Calle Principal 123',
            'birth_date' => '1990-05-15',
            'gender' => 'femenino',
            'document_type' => 'cedula',
            'document_number' => 'REC001',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $receptionist->assignRole('recepcionista');

        // Crear doctores
        $specialties = Specialty::all();
        
        $doctors = [
            [
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@consultorio.com',
                'license' => 'MED001',
                'document_type' => 'cedula',
                'document_number' => '12345678',
                'specialty_id' => $specialties->where('name', 'Cardiología')->first()->id,
                'education' => 'MD en Cardiología, Universidad Nacional',
                'years_experience' => 15,
                'bio' => 'Especialista en cardiología con 15 años de experiencia.',
            ],
            [
                'name' => 'Dra. Ana López',
                'email' => 'ana.lopez@consultorio.com',
                'license' => 'MED002',
                'document_type' => 'cedula',
                'document_number' => '87654321',
                'specialty_id' => $specialties->where('name', 'Pediatría')->first()->id,
                'education' => 'MD en Pediatría, Universidad Central',
                'years_experience' => 12,
                'bio' => 'Pediatra especializada en medicina infantil.',
            ],
            [
                'name' => 'Dr. Carlos Rodríguez',
                'email' => 'carlos.rodriguez@consultorio.com',
                'license' => 'MED003',
                'document_type' => 'cedula',
                'document_number' => '11223344',
                'specialty_id' => $specialties->where('name', 'Medicina General')->first()->id,
                'education' => 'MD en Medicina General, Universidad del Valle',
                'years_experience' => 8,
                'bio' => 'Médico general con enfoque en medicina familiar.',
            ],
            [
                'name' => 'Dra. Laura Martínez',
                'email' => 'laura.martinez@consultorio.com',
                'license' => 'MED004',
                'document_type' => 'cedula',
                'document_number' => '44332211',
                'specialty_id' => $specialties->where('name', 'Ginecología')->first()->id,
                'education' => 'MD en Ginecología, Universidad Nacional',
                'years_experience' => 10,
                'bio' => 'Ginecóloga especializada en salud reproductiva.',
            ],
        ];

        foreach ($doctors as $doctorData) {
            $user = User::create([
                'name' => $doctorData['name'],
                'email' => $doctorData['email'],
                'password' => Hash::make('password'),
                'phone' => '123-456-' . rand(1000, 9999),
                'address' => 'Consultorio Médico',
                'birth_date' => now()->subYears(rand(30, 60)),
                'gender' => str_contains($doctorData['name'], 'Dra.') ? 'femenino' : 'masculino',
                'document_type' => $doctorData['document_type'],
                'document_number' => $doctorData['document_number'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            $user->assignRole('medico');
            
            Doctor::create([
                'user_id' => $user->id,
                'license_number' => $doctorData['license'],
                'specialty_id' => $doctorData['specialty_id'],
                'education' => $doctorData['education'],
                'years_experience' => $doctorData['years_experience'],
                'bio' => $doctorData['bio'],
                'schedule' => [
                    'lunes' => ['inicio' => '08:00', 'fin' => '17:00'],
                    'martes' => ['inicio' => '08:00', 'fin' => '17:00'],
                    'miercoles' => ['inicio' => '08:00', 'fin' => '17:00'],
                    'jueves' => ['inicio' => '08:00', 'fin' => '17:00'],
                    'viernes' => ['inicio' => '08:00', 'fin' => '15:00'],
                ],
                'consultation_fee' => rand(50, 150),
                'is_available' => true,
            ]);
        }

        // Crear pacientes
        $patients = [
            [
                'name' => 'Pedro Jiménez',
                'email' => 'pedro.jimenez@email.com',
                'birth_date' => '1985-03-20',
                'gender' => 'masculino',
                'document_type' => 'cedula',
                'document_number' => 'PAC001111',
                'blood_type' => 'O+',
            ],
            [
                'name' => 'María Fernández',
                'email' => 'maria.fernandez@email.com',
                'birth_date' => '1992-07-12',
                'gender' => 'femenino',
                'document_type' => 'cedula',
                'document_number' => 'PAC002222',
                'blood_type' => 'A+',
            ],
            [
                'name' => 'José García',
                'email' => 'jose.garcia@email.com',
                'birth_date' => '1978-11-05',
                'gender' => 'masculino',
                'document_type' => 'cedula',
                'document_number' => 'PAC003333',
                'blood_type' => 'B+',
            ],
            [
                'name' => 'Carmen Silva',
                'email' => 'carmen.silva@email.com',
                'birth_date' => '1995-09-18',
                'gender' => 'femenino',
                'document_type' => 'pasaporte',
                'document_number' => 'PAC004444',
                'blood_type' => 'AB+',
            ],
            [
                'name' => 'Luis Morales',
                'email' => 'luis.morales@email.com',
                'birth_date' => '1988-12-30',
                'gender' => 'masculino',
                'document_type' => 'cedula',
                'document_number' => 'PAC005555',
                'blood_type' => 'O-',
            ],
        ];

        foreach ($patients as $patientData) {
            $user = User::create([
                'name' => $patientData['name'],
                'email' => $patientData['email'],
                'password' => Hash::make('password'),
                'phone' => '123-456-' . rand(1000, 9999),
                'address' => 'Dirección del paciente ' . rand(100, 999),
                'birth_date' => $patientData['birth_date'],
                'gender' => $patientData['gender'],
                'document_type' => $patientData['document_type'],
                'document_number' => $patientData['document_number'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            
            $user->assignRole('paciente');
            
            Patient::create([
                'user_id' => $user->id,
                'emergency_contact_name' => 'Contacto de emergencia',
                'emergency_contact_phone' => '123-456-' . rand(1000, 9999),
                'insurance_provider' => 'Seguro Médico Nacional',
                'insurance_number' => 'SEG' . rand(100000, 999999),
                'allergies' => 'Ninguna conocida',
                'medical_conditions' => 'Ninguna',
                'medications' => 'Ninguna',
                'blood_type' => $patientData['blood_type'],
                'height' => rand(150, 190),
                'weight' => rand(50, 100),
            ]);
        }
    }
}
