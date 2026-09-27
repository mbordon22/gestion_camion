<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opcional, igual que el cliente: un viaje a medio cargar no se traba por
     * esto. A diferencia de los clientes no se rellenan los viajes viejos:
     * no hay dato de quién manejó y no se inventa.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->foreignId('chofer_id')->nullable()->after('cliente_id')
                ->constrained('choferes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chofer_id');
        });
    }
};
