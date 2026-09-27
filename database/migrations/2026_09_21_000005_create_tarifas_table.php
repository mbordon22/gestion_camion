<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que se cobra por unidad según la distancia del viaje. Nace con la
     * vinaza: EFASS paga por tonelada, y el importe depende del rango de km
     * (0-8, 9-15, 16-35). Hasta ahora esa regla vivía en la cabeza del usuario.
     *
     * La tarifa NO es una forma de cobro nueva: sólo propone el precio por
     * unidad al cargar el viaje, y se puede pisar a mano. El rango que se
     * aplicó queda implícito en los km del viaje.
     *
     * 'vigente_desde' es lo que permite cambiar precios sin perder la historia:
     * una tarifa nueva no reemplaza a la vieja, la tapa desde esa fecha. Como
     * el total queda grabado en cada viaje, tocar tarifas nunca reescribe lo
     * ya cargado.
     */
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();

            // Vacío = vale para cualquier carga. Con valor, le gana a la general.
            $table->string('producto', 60)->nullable();

            $table->unsignedInteger('km_desde');
            $table->unsignedInteger('km_hasta');
            $table->decimal('importe', 12, 2);

            // Por qué unidad es ese importe. Sirve para avisar si el viaje se
            // cargó en otra (kilos contra toneladas es un error de 1000x).
            $table->string('unidad', 20)->default('toneladas');

            $table->date('vigente_desde');
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['cliente_id', 'vigente_desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
