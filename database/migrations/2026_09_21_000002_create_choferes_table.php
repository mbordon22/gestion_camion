<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quién maneja en cada viaje. Mismo criterio que clientes: pocos campos,
     * lo que no entre (licencia, obra social, contacto) va en notas.
     */
    public function up(): void
    {
        Schema::create('choferes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('dni', 10)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('choferes');
    }
};
