<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primero recrear geografía (países/provincias/ciudades) tal como pediste
        $this->call([
            CountryProvinceCitySeeder::class,
            ArgentinaFullGeoSeeder::class,
        ]);

        // Seeders base y roles
        $this->call([
            RoleSeeder::class,
            SpecialtySeeder::class,
            PatientTypeSeeder::class,
            InsuranceProviderSeeder::class,
            UserSeeder::class,
        ]);

        // Seeder masivo que crea doctores, pacientes y citas (~800)
        $this->call([
            \Database\Seeders\MassiveTestDataSeeder::class,
        ]);

            // Seeders para configuración: motivos y feriados
            $this->call([
                \Database\Seeders\AppointmentReasonsTableSeeder::class,
                \Database\Seeders\HolidaysTableSeeder::class,
                StudyTypeSeeder::class,
            ]);
    }
}
