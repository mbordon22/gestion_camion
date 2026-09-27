<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué se lleva en el viaje. Hasta ahora era azúcar y estaba implícito;
     * con la vinaza dejó de estarlo. No es lo mismo que 'unidad': "toneladas"
     * no dice si son toneladas de vinaza o de cereal.
     *
     * Texto libre y no una tabla: la lista de productos de un camión es corta
     * y cambia sola, el formulario sugiere los que ya se usaron.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->string('producto', 60)->nullable()->after('nro_orden');
        });

        // Los viajes en bolsas son los del ingenio: todos fueron azúcar.
        // Los de monto fijo sin unidad no se tocan, no hay cómo saberlo.
        DB::table('viajes')->where('unidad', 'bolsas')->update(['producto' => 'Azúcar']);
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn('producto');
        });
    }
};
