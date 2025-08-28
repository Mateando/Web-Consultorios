<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;

class CountryProvinceCitySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Argentina' => [
                'iso' => 'ARG',
                'provinces' => [
                    'Buenos Aires' => ['La Plata','Mar del Plata','Bahía Blanca','Tandil'],
                    'Córdoba' => ['Córdoba','Villa Carlos Paz','Río Cuarto'],
                    'Santa Fe' => ['Santa Fe','Rosario','Rafaela'],
                ],
            ],
            'Chile' => [
                'iso' => 'CHL',
                'provinces' => [
                    'Región Metropolitana' => ['Santiago','Puente Alto','Maipú'],
                    'Valparaíso' => ['Valparaíso','Viña del Mar'],
                ],
            ],
            'Uruguay' => [
                'iso' => 'URY',
                'provinces' => [
                    'Montevideo' => ['Montevideo'],
                    'Canelones' => ['Las Piedras','Pando'],
                ],
            ],
        ];

        foreach ($data as $countryName => $info) {
            $country = Country::firstOrCreate(['name' => $countryName], [
                'iso_code' => $info['iso'],
            ]);
            foreach ($info['provinces'] as $provName => $cities) {
                $province = Province::firstOrCreate([
                    'country_id' => $country->id,
                    'name' => $provName,
                ]);
                foreach ($cities as $cityName) {
                    City::firstOrCreate([
                        'province_id' => $province->id,
                        'name' => $cityName,
                    ]);
                }
            }
        }
    }
}
