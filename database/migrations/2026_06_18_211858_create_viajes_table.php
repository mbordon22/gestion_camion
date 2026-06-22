<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viajes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nro_ingreso')->nullable();
            $table->integer('bolsas');
            $table->decimal('precio_bolsa', 10, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('kg_netos', 10, 2)->nullable();
            $table->string('destino')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viajes');
    }
};
