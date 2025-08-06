<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use Spatie\Permission\Models\Role;

// Verificar usuarios existentes
$users = User::all();
echo "Usuarios existentes:\n";
foreach ($users as $user) {
    echo "- {$user->email} ({$user->name})\n";
}

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
        echo "\nUsuario administrador creado: admin@test.com / password\n";
    } else {
        echo "\nRol 'administrador' no existe\n";
    }
} else {
    echo "\nUsuario administrador ya existe: {$admin->email}\n";
}
