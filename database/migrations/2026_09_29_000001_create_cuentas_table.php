<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada cliente del sistema (un transportista, con uno o más camiones) es
     * una cuenta: sus datos no se mezclan con los de otra. No hay registro
     * público; las cuentas las crea el administrador desde su panel.
     *
     * 'activa' en falso suspende la cuenta: sus usuarios no pueden entrar,
     * pero no se borra nada.
     */
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('notas')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
