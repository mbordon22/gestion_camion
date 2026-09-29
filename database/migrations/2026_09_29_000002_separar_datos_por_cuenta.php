<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Las tablas con datos de un cliente del sistema: todas llevan su cuenta. */
    private array $tablas = [
        'camiones', 'viajes', 'combustible', 'mantenimiento', 'medios_pago',
        'clientes', 'choferes', 'chofer_movimientos', 'liquidaciones',
        'destinos', 'tarifas', 'equipos', 'productos',
    ];

    /**
     * Lo que antes era único en todo el sistema pasa a serlo dentro de cada
     * cuenta: dos transportistas pueden tener un cliente que se llame igual,
     * o un chofer, o un camión con la misma patente cargada.
     */
    private array $unicos = [
        'camiones'  => ['patente'],
        'clientes'  => ['nombre'],
        'choferes'  => ['nombre'],
        'equipos'   => ['nombre'],
        'productos' => ['nombre'],
        'destinos'  => ['cliente_id', 'nombre'],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable()->after('id')->constrained('cuentas');
            $table->boolean('es_admin')->default(false)->after('password');
        });

        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->foreignId('cuenta_id')->nullable()->after('id')->constrained('cuentas');
            });
        }

        $this->pasarLoQueHabiaAUnaCuenta();

        foreach ($this->unicos as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla, $columnas) {
                // El índice único de destinos es el que usa la clave foránea
                // de cliente_id: antes de sacarlo, uno propio para ella.
                if ($tabla === 'destinos') {
                    $table->index('cliente_id');
                }

                $table->dropUnique($columnas);
                $table->unique(['cuenta_id', ...$columnas]);
            });
        }
    }

    /**
     * Todo lo que ya estaba cargado es de una sola cuenta, la del primer
     * usuario, que además es el administrador del sistema. Sin datos (una
     * instalación nueva) no se crea nada.
     */
    private function pasarLoQueHabiaAUnaCuenta(): void
    {
        $hayDatos = DB::table('users')->exists()
            || collect($this->tablas)->contains(fn ($tabla) => DB::table($tabla)->exists());

        if (! $hayDatos) {
            return;
        }

        $primero = DB::table('users')->orderBy('id')->first();

        $cuentaId = DB::table('cuentas')->insertGetId([
            'nombre'     => $primero?->name ?? 'Cuenta principal',
            'activa'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['users', ...$this->tablas] as $tabla) {
            DB::table($tabla)->whereNull('cuenta_id')->update(['cuenta_id' => $cuentaId]);
        }

        if ($primero) {
            DB::table('users')->where('id', $primero->id)->update(['es_admin' => true]);
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            $columnas = $this->unicos[$tabla] ?? null;

            Schema::table($tabla, function (Blueprint $table) use ($tabla, $columnas) {
                // Primero la clave foránea: en MySQL el índice único (cuenta_id, …)
                // es el que la sostiene y no se puede borrar mientras exista.
                $table->dropForeign(['cuenta_id']);

                if ($columnas) {
                    $table->dropUnique(['cuenta_id', ...$columnas]);
                    $table->unique($columnas);
                }

                if ($tabla === 'destinos') {
                    $table->dropIndex(['cliente_id']);
                }

                $table->dropColumn('cuenta_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cuenta_id');
            $table->dropColumn('es_admin');
        });
    }
};
