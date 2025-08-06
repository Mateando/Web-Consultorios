<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles si no existen
        Role::firstOrCreate(['name' => 'admin']);
        
        // Crear usuario admin
        if (!User::where('email', 'admin@consultorio.com')->exists()) {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => 'admin@consultorio.com',
                'password' => Hash::make('admin123'),
                'phone' => '555-0000',
                'is_active' => true,
                'document_type' => 'cedula',
                'document_number' => '00000000',
            ]);

            $admin->assignRole('admin');
            $this->command->info('Usuario admin creado: admin@consultorio.com / admin123');
        }
    }
}
