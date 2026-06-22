<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedioPago;

class MedioPagoSeeder extends Seeder
{
    public function run(): void
    {
        MedioPago::firstOrCreate(
            ['nombre' => 'Efectivo'],
            ['tipo' => 'efectivo', 'activo' => true]
        );

        MedioPago::firstOrCreate(
            ['nombre' => 'Transferencia'],
            ['tipo' => 'transferencia', 'activo' => true]
        );
    }
}
