<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Patient;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class PatientUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear rol de paciente si no existe
    Role::firstOrCreate(['name' => 'paciente']);
        
        // Crear usuario paciente de prueba
        if (!User::where('email', 'paciente@ejemplo.com')->exists()) {
            $user = User::create([
                'name' => 'María Paciente',
                'email' => 'paciente@ejemplo.com',
                'password' => Hash::make('password123'),
                'phone' => '555-3333',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '33445566',
            ]);

            $user->assignRole('paciente');

            Patient::create([
                'user_id' => $user->id,
                'emergency_contact_name' => 'Juan Paciente',
                'emergency_contact_phone' => '555-4444',
                'blood_type' => 'O+',
            ]);

            $this->command->info('Usuario paciente creado: paciente@ejemplo.com / password123');
        }
    }
}
