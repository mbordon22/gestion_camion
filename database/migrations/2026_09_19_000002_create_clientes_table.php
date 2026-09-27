<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Para quién se hace el viaje. Pocos campos a propósito: lo que no entre
     * acá (contacto, condiciones de pago, dirección) va en notas.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('cuit', 13)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
