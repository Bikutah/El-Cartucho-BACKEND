<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ZonaEnvio;

class ZonaEnvioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zonas = [
            [
                'nombre' => 'Chubut',
                'cp_desde' => 9000,
                'cp_hasta' => 9299,
                'costo' => 8000.00,
                'activa' => true,
                'orden' => 0,
            ],
            [
                'nombre' => 'Patagonia',
                'cp_desde' => 8300,
                'cp_hasta' => 9999,
                'costo' => 12000.00,
                'activa' => true,
                'orden' => 1,
            ],
            [
                'nombre' => 'Centro / Buenos Aires',
                'cp_desde' => 1000,
                'cp_hasta' => 8299,
                'costo' => 15000.00,
                'activa' => true,
                'orden' => 2,
            ],
            [
                'nombre' => 'Norte',
                'cp_desde' => 3000,
                'cp_hasta' => 4999,
                'costo' => 18000.00,
                'activa' => true,
                'orden' => 3,
            ],
        ];

        foreach ($zonas as $zona) {
            ZonaEnvio::updateOrCreate(
                ['nombre' => $zona['nombre']],
                $zona
            );
        }
    }
}
