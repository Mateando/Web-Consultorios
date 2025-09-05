<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StudyType;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

class StudyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Ecografía Abdominal', 'cost' => 15000],
            ['name' => 'Radiografía Tórax', 'cost' => 12000],
            ['name' => 'Electrocardiograma', 'cost' => 8000],
            ['name' => 'Ergometría', 'cost' => 25000],
            ['name' => 'Laboratorio Básico', 'cost' => 6000],
        ];

        foreach ($defaults as $d) {
            StudyType::firstOrCreate(['name' => $d['name']], [
                'description' => $d['name'],
                'cost' => $d['cost'],
                'is_active' => true,
            ]);
        }

        $studyTypes = StudyType::all();
        $doctors = Doctor::with('studyTypes')->get();

        // Asociar aleatoriamente 2-3 estudios a cada doctor si no tiene
        foreach ($doctors as $doc) {
            if ($doc->studyTypes()->count() === 0) {
                $attach = $studyTypes->shuffle()->take(rand(2, min(3, $studyTypes->count())))->pluck('id')->all();
                $doc->studyTypes()->attach($attach);
            }
        }
    }
}
