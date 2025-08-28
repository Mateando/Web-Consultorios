<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PatientType;

class PatientTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Particular',
            'Obra Social',
            'Prepaga',
            'Convenio',
            'Emergencia',
        ];
        foreach ($items as $name) {
            PatientType::firstOrCreate(['name' => $name]);
        }
    }
}
