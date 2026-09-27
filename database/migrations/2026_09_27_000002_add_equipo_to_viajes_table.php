<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada viaje graba cuánto se lleva el dueño del equipo, igual que graba el
     * total: si mañana el alquiler pasa del 25% al 20%, los viajes ya
     * cargados no cambian. El porcentaje queda para saber de dónde salió el
     * monto.
     *
     * alquiler_pagado_el es el día que se le pagó al dueño; vacío es que
     * todavía se le debe.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->foreignId('equipo_id')->nullable()->after('chofer_id')
                ->constrained('equipos')->restrictOnDelete();
            $table->decimal('alquiler_porcentaje', 5, 2)->nullable()->after('total');
            $table->decimal('alquiler_monto', 12, 2)->nullable()->after('alquiler_porcentaje');
            $table->date('alquiler_pagado_el')->nullable()->after('alquiler_monto');
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_id');
            $table->dropColumn(['alquiler_porcentaje', 'alquiler_monto', 'alquiler_pagado_el']);
        });
    }
};
