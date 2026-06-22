<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Viaje;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MedioPagoSeeder::class);

        /* $hoy = Carbon::today();

        Viaje::insert([
            [
                'fecha'        => $hoy->copy()->subDays(2)->toDateString(),
                'fecha_carga'  => $hoy->copy()->subDays(1)->toDateString(),
                'nro_ingreso'  => 'ING-001',
                'bolsas'       => 500,
                'precio_bolsa' => 1200.00,
                'total'        => 600000.00,
                'kg_netos'     => 25000.00,
                'destino'      => 'Tucumán',
                'observaciones'=> 'Entrega sin novedad',
                'created_at'   => now(),
            ],
            [
                'fecha'        => $hoy->copy()->subDays(5)->toDateString(),
                'fecha_carga'  => $hoy->copy()->subDays(4)->toDateString(),
                'nro_ingreso'  => 'ING-002',
                'bolsas'       => 420,
                'precio_bolsa' => 1200.00,
                'total'        => 504000.00,
                'kg_netos'     => 21000.00,
                'destino'      => 'Jujuy',
                'observaciones'=> null,
                'created_at'   => now(),
            ],
            [
                'fecha'        => $hoy->copy()->subDays(10)->toDateString(),
                'fecha_carga'  => $hoy->copy()->subDays(8)->toDateString(),
                'nro_ingreso'  => 'ING-003',
                'bolsas'       => 600,
                'precio_bolsa' => 1150.00,
                'total'        => 690000.00,
                'kg_netos'     => 30000.00,
                'destino'      => 'Salta',
                'observaciones'=> 'Pesaje en destino',
                'created_at'   => now(),
            ],
        ]);

        Combustible::insert([
            [
                'fecha'       => $hoy->copy()->subDays(3)->toDateString(),
                'litros'      => 300.00,
                'precio_litro'=> 1050.00,
                'total'       => 315000.00,
                'km_odometro' => 125000,
                'lugar'       => 'YPF Ruta 9',
                'created_at'  => now(),
            ],
            [
                'fecha'       => $hoy->copy()->subDays(8)->toDateString(),
                'litros'      => 280.00,
                'precio_litro'=> 1030.00,
                'total'       => 288400.00,
                'km_odometro' => 124200,
                'lugar'       => 'Shell Acceso Norte',
                'created_at'  => now(),
            ],
        ]);

        Mantenimiento::insert([
            [
                'fecha'          => $hoy->copy()->subDays(15)->toDateString(),
                'tipo'           => 'service',
                'monto'          => 85000.00,
                'km_actuales'    => 124000,
                'proximo_service'=> 134000,
                'detalle'        => 'Service completo: aceite 15W40, filtros aceite y aire, revisión general',
                'created_at'     => now(),
            ],
        ]); */
    }
}
