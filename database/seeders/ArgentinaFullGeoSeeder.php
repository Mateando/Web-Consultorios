<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\Province;
use App\Models\City;
use Illuminate\Support\Facades\DB;

class ArgentinaFullGeoSeeder extends Seeder
{
    public function run(): void
    {
        // Fuente: INDEC / listado estándar (abreviado) - se incluyen ciudades principales y cabeceras departamentales.
        // Para evitar duplicados si ya existe Argentina del seeder previo, la reutilizamos.
        $country = Country::firstOrCreate([
            'name' => 'Argentina'
        ], [
            'iso_code' => 'AR',
            'is_active' => true,
        ]);

        // Provincias y ciudades principales (no exhaustivo de cada localidad mínima, pero amplio).
        $data = [
            'Buenos Aires' => [
                'La Plata','Mar del Plata','Bahía Blanca','Tandil','Olavarría','Quilmes','Avellaneda','Lanús','Lomas de Zamora','Morón','San Isidro','Tigre','San Nicolás de los Arroyos','Pergamino','Junín','Necochea','San Pedro','Pinamar','Villa Gesell'
            ],
            'Catamarca' => ['San Fernando del Valle de Catamarca','Tinogasta','Belén','Santa María','Andalgalá','Recreo'],
            'Chaco' => ['Resistencia','Barranqueras','Fontana','Puerto Vilelas','Presidencia Roque Sáenz Peña','Villa Ángela','Charata','Las Breñas'],
            'Chubut' => ['Rawson','Trelew','Puerto Madryn','Comodoro Rivadavia','Esquel','Sarmiento','Gaiman'],
            'Córdoba' => ['Córdoba','Villa Carlos Paz','Río Cuarto','Villa María','San Francisco','Alta Gracia','Bell Ville','La Falda','Cosquín','Villa General Belgrano'],
            'Corrientes' => ['Corrientes','Goya','Paso de los Libres','Curuzú Cuatiá','Mercedes','Ituzaingó','Santo Tomé'],
            'Entre Ríos' => ['Paraná','Concordia','Gualeguaychú','Concepción del Uruguay','Villaguay','Gualeguay','La Paz','Nogoyá'],
            'Formosa' => ['Formosa','Clorinda','Pirané','El Colorado','Laguna Blanca'],
            'Jujuy' => ['San Salvador de Jujuy','Palpalá','Perico','Libertador General San Martín','San Pedro','Humahuaca','Tilcara'],
            'La Pampa' => ['Santa Rosa','General Pico','Toay','Victorica','Realicó'],
            'La Rioja' => ['La Rioja','Chilecito','Aimogasta','Chamical','Chepes'],
            'Mendoza' => ['Mendoza','San Rafael','Malargüe','Tunuyán','San Martín','Godoy Cruz','Guaymallén','Luján de Cuyo','Las Heras'],
            'Misiones' => ['Posadas','Oberá','Eldorado','Puerto Iguazú','Apóstoles','San Vicente','Leandro N. Alem'],
            'Neuquén' => ['Neuquén','Cutral Có','Plaza Huincul','Zapala','San Martín de los Andes','Junín de los Andes','Villa La Angostura'],
            'Río Negro' => ['Viedma','San Carlos de Bariloche','General Roca','Cipolletti','Villa Regina','Allen'],
            'Salta' => ['Salta','San Ramón de la Nueva Orán','Tartagal','General Güemes','Cafayate','Metán','Rosario de la Frontera'],
            'San Juan' => ['San Juan','Rawson','Chimbas','Rivadavia','Pocito','Albardón','Jáchal','Caucete'],
            'San Luis' => ['San Luis','Villa Mercedes','Merlo','La Punta','Juana Koslay','Santa Rosa del Conlara'],
            'Santa Cruz' => ['Río Gallegos','Caleta Olivia','Puerto Deseado','Pico Truncado','Las Heras','El Calafate'],
            'Santa Fe' => ['Santa Fe','Rosario','Rafaela','Reconquista','Venado Tuerto','Esperanza','Sunchales'],
            'Santiago del Estero' => ['Santiago del Estero','La Banda','Termas de Río Hondo','Añatuya','Quimilí'],
            'Tierra del Fuego' => ['Ushuaia','Río Grande','Tolhuin'],
            'Tucumán' => ['San Miguel de Tucumán','Tafí Viejo','Yerba Buena','Concepción','Monteros','Aguilares']
        ];

        DB::transaction(function () use ($data, $country) {
            foreach ($data as $provinceName => $cities) {
                $province = Province::firstOrCreate([
                    'country_id' => $country->id,
                    'name' => $provinceName,
                ], [
                    'is_active' => true,
                ]);

                foreach ($cities as $cityName) {
                    City::firstOrCreate([
                        'province_id' => $province->id,
                        'name' => $cityName,
                    ], [
                        'is_active' => true,
                    ]);
                }
            }
        });
    }
}
