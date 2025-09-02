<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

$englishRoles = ['admin', 'doctor', 'patient', 'receptionist'];

foreach ($englishRoles as $r) {
    $role = Role::where('name', $r)->first();
    if (!$role) {
        echo "Rol '{$r}' no existe en la base, ignorando.\n";
        continue;
    }

    // Remover asignaciones en model_has_roles
    DB::table('model_has_roles')->where('role_id', $role->id)->delete();
    // Borrar el rol
    $role->delete();
    echo "Rol '{$r}' y sus asignaciones eliminadas.\n";
}

echo "Proceso de eliminación completado.\n";
