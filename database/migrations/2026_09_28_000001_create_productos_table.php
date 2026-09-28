<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que se lleva en los viajes. Hasta ahora se escribía a mano con
     * sugerencias, y un "vinaza" tipeado distinto dejaba de encontrar su
     * tarifa, que se busca por nombre.
     *
     * 'unidad' es cómo se mide habitualmente (la vinaza en toneladas, el
     * azúcar en bolsas): al elegir el producto en el viaje se completa sola.
     *
     * Igual que con los destinos, el viaje y la tarifa siguen guardando el
     * producto como texto: los viajes viejos quedan como se cargaron.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('unidad', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        $this->sembrarConLoYaCargado();
    }

    /**
     * Arranca el catálogo con los productos de los viajes y de las tarifas,
     * para que el selector no aparezca vacío. La unidad es la más usada con
     * ese producto.
     */
    private function sembrarConLoYaCargado(): void
    {
        $usados = DB::table('viajes')->whereNotNull('producto')->get(['producto', 'unidad'])
            ->concat(DB::table('tarifas')->whereNotNull('producto')->get(['producto'])
                ->map(fn ($tarifa) => (object) ['producto' => $tarifa->producto, 'unidad' => null]))
            ->map(fn ($fila) => (object) ['producto' => trim((string) $fila->producto), 'unidad' => $fila->unidad])
            ->reject(fn ($fila) => $fila->producto === '');

        // "Vinaza" y "vinaza" son lo mismo escrito distinto (y el índice único
        // de MySQL tampoco los distingue): queda la grafía más usada.
        $usados->groupBy(fn ($fila) => mb_strtolower($fila->producto))
            ->each(function ($filas) {
                DB::table('productos')->insert([
                    'nombre'     => $filas->countBy('producto')->sortDesc()->keys()->first(),
                    'unidad'     => $filas->pluck('unidad')->filter()->countBy()->sortDesc()->keys()->first(),
                    'activo'     => true,
                    'created_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
