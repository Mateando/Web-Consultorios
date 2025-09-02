<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialty;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DoctorSpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles si no existen
    Role::firstOrCreate(['name' => 'administrador']);
    Role::firstOrCreate(['name' => 'medico']);
    Role::firstOrCreate(['name' => 'paciente']);
    Role::firstOrCreate(['name' => 'recepcionista']);

        // Crear especialidades si no existen
        $cardiologia = Specialty::firstOrCreate(['name' => 'Cardiología']);
        $neurologia = Specialty::firstOrCreate(['name' => 'Neurología']);
        $traumatologia = Specialty::firstOrCreate(['name' => 'Traumatología']);
        $medicina_interna = Specialty::firstOrCreate(['name' => 'Medicina Interna']);
        $pediatria = Specialty::firstOrCreate(['name' => 'Pediatría']);

        // Crear doctor con múltiples especialidades
        if (!User::where('email', 'juan.perez@ejemplo.com')->exists()) {
            $user1 = User::create([
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@ejemplo.com',
                'password' => Hash::make('password123'),
                'phone' => '555-1234',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '12345678',
            ]);

            $user1->assignRole('medico');

            $doctor1 = Doctor::create([
                'user_id' => $user1->id,
                'license_number' => 'MED-12345',
                'consultation_fee' => 150.00
            ]);

            // Asignar múltiples especialidades
            $doctor1->specialties()->sync([$cardiologia->id, $neurologia->id]);
        }

        // Crear otro doctor con especialidades diferentes
        if (!User::where('email', 'maria.lopez@ejemplo.com')->exists()) {
            $user2 = User::create([
                'name' => 'Dra. María López',
                'email' => 'maria.lopez@ejemplo.com',
                'password' => Hash::make('password123'),
                'phone' => '555-5678',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '87654321',
            ]);

            $user2->assignRole('medico');

            $doctor2 = Doctor::create([
                'user_id' => $user2->id,
                'license_number' => 'MED-54321',
                'consultation_fee' => 200.00
            ]);

            // Asignar especialidades diferentes
            $doctor2->specialties()->sync([$traumatologia->id, $medicina_interna->id]);
        }

        // Crear un doctor solo con pediatría
        if (!User::where('email', 'carlos.rodriguez@ejemplo.com')->exists()) {
            $user3 = User::create([
                'name' => 'Dr. Carlos Rodríguez',
                'email' => 'carlos.rodriguez@ejemplo.com',
                'password' => Hash::make('password123'),
                'phone' => '555-9999',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '11223344',
            ]);

            $user3->assignRole('medico');

            $doctor3 = Doctor::create([
                'user_id' => $user3->id,
                'license_number' => 'MED-99999',
                'consultation_fee' => 120.00
            ]);

            $doctor3->specialties()->sync([$pediatria->id]);
        }

        // Crear un doctor que tenga cardiología y traumatología
        if (!User::where('email', 'ana.garcia@ejemplo.com')->exists()) {
            $user4 = User::create([
                'name' => 'Dra. Ana García',
                'email' => 'ana.garcia@ejemplo.com',
                'password' => Hash::make('password123'),
                'phone' => '555-7777',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '55667788',
            ]);

            $user4->assignRole('medico');

            $doctor4 = Doctor::create([
                'user_id' => $user4->id,
                'license_number' => 'MED-77777',
                'consultation_fee' => 180.00
            ]);

            $doctor4->specialties()->sync([$cardiologia->id, $traumatologia->id]);
        }

        $this->command->info('Doctores con múltiples especialidades creados exitosamente.');
    }
}
