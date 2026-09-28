<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cómo cobra el chofer: un porcentaje del bruto de cada viaje o un monto
     * fijo por viaje. Vacío es que no cobra por viaje (maneja el dueño, o
     * tiene sueldo): a sus viajes no se les descuenta nada.
     */
    public function up(): void
    {
        Schema::table('choferes', function (Blueprint $table) {
            $table->string('modalidad', 20)->nullable()->after('telefono');
            $table->decimal('valor', 12, 2)->nullable()->after('modalidad');
        });
    }

    public function down(): void
    {
        Schema::table('choferes', function (Blueprint $table) {
            $table->dropColumn(['modalidad', 'valor']);
        });
    }
};
