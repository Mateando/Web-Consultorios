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
        // Crear roles
        $adminRole = Role::create(['name' => 'administrador']);
        $doctorRole = Role::create(['name' => 'medico']);
        $patientRole = Role::create(['name' => 'paciente']);
        $receptionistRole = Role::create(['name' => 'recepcionista']);

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
            Permission::create(['name' => $permission]);
        }

        // Asignar permisos a roles
        $adminRole->givePermissionTo($permissions); // Admin tiene todos los permisos

        $doctorRole->givePermissionTo([
            'ver_pacientes',
            'ver_citas',
            'editar_citas',
            'completar_citas',
            'ver_historiales',
            'crear_historiales',
            'editar_historiales',
            'editar_doctor_perfil',
        ]);

        $receptionistRole->givePermissionTo([
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

        $patientRole->givePermissionTo([
            'ver_citas',
            'crear_citas',
            'ver_historiales',
        ]);
    }
}
