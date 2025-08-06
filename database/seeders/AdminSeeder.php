<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Crear admin si no existe
        $admin = User::where('email', 'admin@test.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => 'admin@test.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            
            // Asignar rol de administrador
            if (Role::where('name', 'administrador')->exists()) {
                $admin->assignRole('administrador');
                echo "Usuario administrador creado: admin@test.com / password\n";
            }
        } else {
            echo "Usuario administrador ya existe: {$admin->email}\n";
        }
    }
}
