<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medios_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo'); // efectivo, debito, transferencia, credito
            $table->unsignedTinyInteger('dia_cierre')->nullable();      // solo crédito
            $table->unsignedTinyInteger('dia_vencimiento')->nullable(); // solo crédito
            $table->boolean('activo')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medios_pago');
    }
};
