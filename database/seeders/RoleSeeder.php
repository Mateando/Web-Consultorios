<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    // Crear roles si no existen
    $adminRole = Role::firstOrCreate(['name' => 'administrador']);
    $doctorRole = Role::firstOrCreate(['name' => 'medico']);
    $patientRole = Role::firstOrCreate(['name' => 'paciente']);
    $receptionistRole = Role::firstOrCreate(['name' => 'recepcionista']);

        // Crear permisos
        $permissions = [
            // Usuarios
            'crear_usuarios',
            'ver_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            
            // Doctores
            'ver_doctores',
            'editar_doctor_perfil',
            
            // Pacientes
            'ver_pacientes',
            'crear_pacientes',
            'editar_pacientes',
            'eliminar_pacientes',
            
            // Citas
            'ver_citas',
            'crear_citas',
            'editar_citas',
            'cancelar_citas',
            'confirmar_citas',
            'completar_citas',
            
            // Historiales médicos
            'ver_historiales',
            'crear_historiales',
            'editar_historiales',
            
            // Especialidades
            'ver_especialidades',
            'crear_especialidades',
            'editar_especialidades',
            'eliminar_especialidades',
            
            // Reportes
            'ver_reportes',
            'generar_reportes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Asignar permisos a roles
    $adminRole->syncPermissions($permissions); // Admin tiene todos los permisos

    $doctorRole->syncPermissions([
            'ver_pacientes',
            'ver_citas',
            'editar_citas',
            'completar_citas',
            'ver_historiales',
            'crear_historiales',
            'editar_historiales',
            'editar_doctor_perfil',
        ]);

    $receptionistRole->syncPermissions([
            'ver_pacientes',
            'crear_pacientes',
            'editar_pacientes',
            'ver_citas',
            'crear_citas',
            'editar_citas',
            'cancelar_citas',
            'confirmar_citas',
            'ver_doctores',
        ]);

    $patientRole->syncPermissions([
            'ver_citas',
            'crear_citas',
            'ver_historiales',
        ]);
    }
}
