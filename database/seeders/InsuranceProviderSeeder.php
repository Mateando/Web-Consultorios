<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsuranceProvider;

class InsuranceProviderSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'OSDE', 'Swiss Medical', 'Galeno', 'Medifé', 'PAMI', 'IOMA', 'Sancor Salud'
        ];
        foreach ($items as $name) {
            InsuranceProvider::firstOrCreate(['name' => $name]);
        }
    }
}
