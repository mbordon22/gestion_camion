<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrarDatosAMysql extends Command
{
    protected $signature = 'datos:migrar-a-mysql {--fuente=sqlite_old} {--destino=mysql}';

    protected $description = 'Copia los datos del SQLite original a MySQL, tabla por tabla.';

    // Orden importa por las claves foráneas. Préstamos y cuotas ya no existen.
    private array $tablas = [
        'medios_pago',
        'viajes',
        'combustible',
        'mantenimiento',
    ];

    public function handle(): int
    {
        $fuente  = $this->option('fuente');
        $destino = $this->option('destino');

        $this->info("Migrando datos de [$fuente] -> [$destino]");

        // Verificar que la fuente exista y se pueda leer
        try {
            DB::connection($fuente)->getPdo();
            DB::connection($destino)->getPdo();
        } catch (\Throwable $e) {
            $this->error('No se pudo conectar: ' . $e->getMessage());
            return self::FAILURE;
        }

        DB::connection($destino)->statement('SET FOREIGN_KEY_CHECKS=0');

        $resumen = [];

        foreach ($this->tablas as $tabla) {
            if (! DB::connection($fuente)->getSchemaBuilder()->hasTable($tabla)) {
                $this->warn("  - $tabla: no existe en la fuente, se omite.");
                continue;
            }

            $filas = DB::connection($fuente)->table($tabla)->get();

            // Limpiar destino para que sea idempotente (se puede correr de nuevo)
            DB::connection($destino)->table($tabla)->truncate();

            if ($filas->isEmpty()) {
                $resumen[$tabla] = 0;
                $this->line("  - $tabla: 0 filas.");
                continue;
            }

            $filas->chunk(200)->each(function ($chunk) use ($tabla, $destino) {
                $datos = $chunk->map(fn ($fila) => (array) $fila)->all();
                DB::connection($destino)->table($tabla)->insert($datos);
            });

            $copiadas = DB::connection($destino)->table($tabla)->count();
            $resumen[$tabla] = $copiadas;
            $this->line("  - $tabla: $copiadas filas copiadas.");
        }

        DB::connection($destino)->statement('SET FOREIGN_KEY_CHECKS=1');

        // Verificación: comparar conteos fuente vs destino
        $this->newLine();
        $this->info('Verificación fuente vs destino:');
        $ok = true;
        foreach ($this->tablas as $tabla) {
            if (! DB::connection($fuente)->getSchemaBuilder()->hasTable($tabla)) {
                continue;
            }
            $origen = DB::connection($fuente)->table($tabla)->count();
            $copia  = $resumen[$tabla] ?? 0;
            $estado = $origen === $copia ? 'OK' : 'DIFERENCIA';
            if ($origen !== $copia) {
                $ok = false;
            }
            $this->line(sprintf('  %-16s fuente=%-5d destino=%-5d [%s]', $tabla, $origen, $copia, $estado));
        }

        $this->newLine();
        if ($ok) {
            $this->info('✓ Migración completada: todos los conteos coinciden.');
            return self::SUCCESS;
        }

        $this->error('✗ Hay diferencias en los conteos. Revisá antes de seguir.');
        return self::FAILURE;
    }
}
