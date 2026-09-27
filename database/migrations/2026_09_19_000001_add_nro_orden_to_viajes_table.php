<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mientras no hubo campo, el número se anotó a mano en observaciones:
     * "Orden de carga: 9409", "Numero de orden 10110"... Sólo se mueve si la
     * observación es eso y nada más; si tiene otro texto, no se toca.
     */
    private const PATRON = '/^\s*(?:orden de carga|n[uú]mero de orden)\s*:?\s*([\w\-\/]{1,30})\s*$/iu';

    /**
     * Número de orden que da el cliente (orden de carga, remito, etc.).
     * Es texto porque puede traer letras, guiones o ceros adelante, y es
     * opcional porque no todos los trabajos lo dan. No es único: dos
     * clientes distintos pueden usar el mismo número.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->string('nro_orden', 30)->nullable()->after('fecha_carga');
        });

        DB::table('viajes')->whereNotNull('observaciones')->get(['id', 'observaciones'])
            ->each(function ($viaje) {
                if (preg_match(self::PATRON, $viaje->observaciones, $m)) {
                    DB::table('viajes')->where('id', $viaje->id)->update([
                        'nro_orden'     => $m[1],
                        'observaciones' => null,
                    ]);
                }
            });
    }

    /** Devuelve el número a observaciones para no perderlo. */
    public function down(): void
    {
        DB::table('viajes')->whereNotNull('nro_orden')->get(['id', 'nro_orden', 'observaciones'])
            ->each(function ($viaje) {
                DB::table('viajes')->where('id', $viaje->id)->update([
                    'observaciones' => trim("Número de orden: {$viaje->nro_orden}\n{$viaje->observaciones}"),
                ]);
            });

        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn('nro_orden');
        });
    }
};
