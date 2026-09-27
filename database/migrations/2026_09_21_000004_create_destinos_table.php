<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los lugares a los que se viaja, con la distancia. Hasta ahora el destino
     * se escribía a mano en cada viaje: además de tipearlo siempre, se colaban
     * variantes del mismo lugar ("CONTROL UNION - SCANIA" y "DEP. CONTROL
     * UNION - SCANIA" son el mismo depósito).
     *
     * 'origen' documenta desde dónde se midieron esos km: "Churqui son 12 km"
     * es cierto saliendo del Ingenio La Corona y falso desde otro lado. Al
     * elegir el destino en el viaje se completan los dos campos.
     *
     * El viaje sigue guardando el destino como texto y no como relación: así
     * los viajes viejos quedan como se cargaron aunque después se corrija o se
     * borre el destino del catálogo.
     */
    public function up(): void
    {
        Schema::create('destinos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('origen', 100)->nullable();
            $table->unsignedInteger('km')->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();

            // El mismo nombre puede existir para dos clientes con distinto km.
            $table->unique(['cliente_id', 'nombre']);
        });

        $this->sembrarDesdeLosViajes();
    }

    /**
     * Arranca el catálogo con los destinos ya usados, para que el selector no
     * aparezca vacío. Los km quedan en blanco: no hay de dónde sacarlos. Si
     * todos los viajes a ese destino fueron del mismo cliente, se le asigna.
     */
    private function sembrarDesdeLosViajes(): void
    {
        DB::table('viajes')->whereNotNull('destino')->get(['destino', 'cliente_id'])
            ->map(fn ($viaje) => (object) [
                'destino'    => trim((string) $viaje->destino),
                'cliente_id' => $viaje->cliente_id,
            ])
            ->reject(fn ($viaje) => $viaje->destino === '')
            // MySQL no distingue mayusculas en el indice unico, y en la
            // practica tampoco el usuario: "CONTROL UNION - SCANIA" y
            // "Control Union - Scania" son el mismo lugar escrito distinto.
            // Se agrupan y queda la grafia mas usada.
            ->groupBy(fn ($viaje) => mb_strtolower($viaje->destino))
            ->each(function ($viajes) {
                $clientes = $viajes->pluck('cliente_id')->filter()->unique();

                DB::table('destinos')->insert([
                    'nombre'     => $viajes->countBy(fn ($viaje) => $viaje->destino)->sortDesc()->keys()->first(),
                    'cliente_id' => $clientes->count() === 1 ? $clientes->first() : null,
                    'activo'     => true,
                    'created_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinos');
    }
};
