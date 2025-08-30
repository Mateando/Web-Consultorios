<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppointmentReason;

class AppointmentReasonsTableSeeder extends Seeder
{
    public function run()
    {
        $items = [
            ['name' => 'Consulta General', 'description' => 'Consulta médica general', 'is_active' => true],
            ['name' => 'Control', 'description' => 'Control de seguimiento', 'is_active' => true],
            ['name' => 'Vacunación', 'description' => 'Aplicación de vacunas', 'is_active' => true],
            ['name' => 'Electrocardiograma', 'description' => 'Estudio cardiológico', 'is_active' => true],
        ];

        foreach ($items as $it) {
            AppointmentReason::updateOrCreate(['name' => $it['name']], $it);
        }
    }
}
