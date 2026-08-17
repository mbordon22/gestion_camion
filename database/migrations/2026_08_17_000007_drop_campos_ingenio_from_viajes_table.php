<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * nro_ingreso, tipo_ingreso, motivo y kg_netos son vocabulario del ingenio
     * azucarero: un transportista que lleve otra cosa no sabe qué poner ahí.
     *
     * OJO: esta migración borra datos. El down() recrea las columnas, pero
     * vacías. Hay un backup en backup-pre-viaje-generico.sql.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn(['nro_ingreso', 'tipo_ingreso', 'motivo', 'kg_netos']);
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->string('nro_ingreso')->nullable()->after('fecha_carga');
            $table->string('tipo_ingreso')->nullable()->after('nro_ingreso');
            $table->string('motivo')->nullable()->after('tipo_ingreso');
            $table->decimal('kg_netos', 10, 2)->nullable()->after('total');
        });
    }
};
