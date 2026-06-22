<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->enum('tipo', ['aceite', 'filtros', 'neumaticos', 'frenos', 'repuesto', 'service', 'otro']);
            $table->decimal('monto', 10, 2);
            $table->integer('km_actuales')->nullable();
            $table->integer('proximo_service')->nullable();
            $table->text('detalle')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento');
    }
};
