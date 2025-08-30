<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidaysTableSeeder extends Seeder
{
    public function run()
    {
        $items = [
            ['name' => 'Año Nuevo', 'date' => '2025-01-01', 'is_recurring' => true, 'notes' => 'Inicio de año', 'is_active' => true],
            ['name' => 'Día del Trabajador', 'date' => '2025-05-01', 'is_recurring' => true, 'notes' => '', 'is_active' => true],
            ['name' => 'Día de la Independencia', 'date' => '2025-09-07', 'is_recurring' => true, 'notes' => '', 'is_active' => true],
            ['name' => 'Navidad', 'date' => '2025-12-25', 'is_recurring' => true, 'notes' => '', 'is_active' => true],
        ];

        foreach ($items as $it) {
            Holiday::updateOrCreate(['name' => $it['name']], $it);
        }
    }
}
