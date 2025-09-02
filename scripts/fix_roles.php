<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Spatie\Permission\Models\Role;
use App\Models\User;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Iniciando corrimiento de roles (inglés -> español)\n";
$mapping = [
    'admin' => 'administrador',
    'doctor' => 'medico',
    'patient' => 'paciente',
    'receptionist' => 'recepcionista',
];

foreach ($mapping as $src => $dst) {
    echo "\nProcesando mapeo: {$src} -> {$dst}\n";

    $dstRole = Role::firstOrCreate(['name' => $dst]);
    $srcRole = Role::where('name', $src)->first();

    if (!$srcRole) {
        echo "  Rol origen '{$src}' no existe. Se creó/aseguró rol destino '{$dst}'.\n";
        continue;
    }

    // Obtener usuarios con rol origen
    $users = User::role($src)->get();
    $count = $users->count();
    echo "  Usuarios con rol '{$src}': {$count}\n";

    foreach ($users as $user) {
        // Asignar rol destino si no lo tiene
        if (!$user->hasRole($dst)) {
            $user->assignRole($dst);
            echo "    - Asignado rol '{$dst}' a {$user->email}\n";
        } else {
            echo "    - Ya tenía rol '{$dst}': {$user->email}\n";
        }
        // Opcional: dejar el rol original por seguridad
    }

    echo "  Hecho para mapeo {$src} -> {$dst}\n";
}

echo "\nProceso completado. Recomiendo que los administradores afectados cierren sesión y vuelvan a iniciar sesión para aplicar permisos actualizados.\n";
