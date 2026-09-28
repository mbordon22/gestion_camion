<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuántos litros gasta el camión cada 100 km. Lo usa el simulador de
     * viajes para calcular el combustible sin tener que escribirlo cada vez.
     * Lo carga el dueño: las cargas de combustible casi nunca traen el
     * odómetro, así que todavía no se puede sacar de ahí.
     */
    public function up(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            $table->decimal('consumo_cada_100km', 5, 1)->nullable()->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            $table->dropColumn('consumo_cada_100km');
        });
    }
};
