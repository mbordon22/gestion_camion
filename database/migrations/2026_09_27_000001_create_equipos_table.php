<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que se engancha al camión para hacer el trabajo: la cisterna de la
     * vinaza, el equipo cañero. Puede ser propio o alquilado, y el que lo
     * alquila se lleva una parte de cada viaje.
     *
     * El dueño es texto libre: no es un cliente ni un proveedor que se cargue
     * en otro lado. El camión es el habitual y es opcional, porque el equipo
     * se elige igual en cada viaje.
     */
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('tipo', 40)->nullable();
            $table->string('patente', 15)->nullable();
            $table->foreignId('camion_id')->nullable()->constrained('camiones')->nullOnDelete();
            $table->boolean('alquilado')->default(false);
            $table->string('propietario', 100)->nullable();
            $table->string('modalidad', 20)->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
