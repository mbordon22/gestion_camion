<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo avanzado que usa cada cuenta (Cuenta::FUNCIONES). Una cuenta nueva
     * arranca sin nada: ve el sistema simple. Las que ya existen quedan con
     * lo que ya estaban usando, así nadie deja de ver lo que tenía cargado.
     */
    public function up(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->json('funciones')->nullable()->after('activa');
        });

        foreach (DB::table('cuentas')->pluck('id') as $cuentaId) {
            $de = fn (string $tabla) => DB::table($tabla)->where('cuenta_id', $cuentaId);

            $usa = array_keys(array_filter([
                'orden'      => $de('viajes')->whereNotNull('nro_orden')->where('nro_orden', '!=', '')->exists(),
                'tarifas'    => $de('tarifas')->exists(),
                'equipos'    => $de('equipos')->exists(),
                'comisiones' => $de('choferes')->whereNotNull('modalidad')->exists()
                    || $de('liquidaciones')->exists()
                    || $de('chofer_movimientos')->exists(),
                'tarjetas'   => $de('medios_pago')->where('tipo', 'credito')->exists(),
            ]));

            DB::table('cuentas')->where('id', $cuentaId)->update(['funciones' => json_encode($usa)]);
        }
    }

    public function down(): void
    {
        Schema::table('cuentas', function (Blueprint $table) {
            $table->dropColumn('funciones');
        });
    }
};
