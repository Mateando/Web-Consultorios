<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialty;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            [
                'name' => 'Medicina General',
                'description' => 'Atención médica general y consultas de primera línea',
                'is_active' => true,
            ],
            [
                'name' => 'Cardiología',
                'description' => 'Especialidad médica que se encarga del estudio, diagnóstico y tratamiento de las enfermedades del corazón',
                'is_active' => true,
            ],
            [
                'name' => 'Pediatría',
                'description' => 'Rama de la medicina que estudia al niño y sus enfermedades',
                'is_active' => true,
            ],
            [
                'name' => 'Ginecología',
                'description' => 'Especialidad médica que trata las enfermedades del sistema reproductor femenino',
                'is_active' => true,
            ],
            [
                'name' => 'Dermatología',
                'description' => 'Especialidad médica que se encarga del estudio de la estructura y función de la piel',
                'is_active' => true,
            ],
            [
                'name' => 'Traumatología',
                'description' => 'Rama de la medicina que se dedica al estudio de las lesiones del aparato locomotor',
                'is_active' => true,
            ],
            [
                'name' => 'Oftalmología',
                'description' => 'Especialidad médica que estudia las enfermedades de los ojos',
                'is_active' => true,
            ],
            [
                'name' => 'Neurología',
                'description' => 'Especialidad médica que trata los trastornos del sistema nervioso',
                'is_active' => true,
            ],
            [
                'name' => 'Psiquiatría',
                'description' => 'Especialidad médica dedicada al estudio de los trastornos mentales',
                'is_active' => true,
            ],
            [
                'name' => 'Endocrinología',
                'description' => 'Rama de la medicina que estudia las glándulas endocrinas y las hormonas',
                'is_active' => true,
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::create($specialty);
        }
    }
}
