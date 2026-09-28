<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada viaje graba la comisión del chofer, igual que el alquiler del
     * equipo: si mañana el chofer pasa del 15% al 18%, los viajes ya cargados
     * no cambian.
     *
     * liquidacion_id es la liquidación en la que se le pagó; vacío es que
     * todavía se le debe.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->decimal('comision_porcentaje', 5, 2)->nullable()->after('alquiler_pagado_el');
            $table->decimal('comision_monto', 12, 2)->nullable()->after('comision_porcentaje');
            $table->foreignId('liquidacion_id')->nullable()->after('comision_monto')
                ->constrained('liquidaciones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('liquidacion_id');
            $table->dropColumn(['comision_porcentaje', 'comision_monto']);
        });
    }
};
