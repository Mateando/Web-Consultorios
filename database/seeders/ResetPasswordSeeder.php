<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetPasswordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Resetear contraseña del administrador
        $admin = User::where('email', 'admin@consultorio.com')->first();
        if ($admin) {
            $admin->update([
                'password' => Hash::make('password')
            ]);
            $this->command->info('Contraseña del administrador actualizada: email: admin@consultorio.com, password: password');
        }

        // Resetear contraseña de la recepcionista
        $reception = User::where('email', 'recepcion@consultorio.com')->first();
        if ($reception) {
            $reception->update([
                'password' => Hash::make('password')
            ]);
            $this->command->info('Contraseña de recepción actualizada: email: recepcion@consultorio.com, password: password');
        }

        // Resetear contraseña de todos los doctores
        $doctors = User::whereHas('roles', function($query) {
            $query->where('name', 'medico');
        })->get();
        
        foreach ($doctors as $doctor) {
            $doctor->update([
                'password' => Hash::make('password')
            ]);
            $this->command->info('Contraseña del doctor ' . $doctor->name . ' actualizada: email: ' . $doctor->email . ', password: password');
        }
    }
}
