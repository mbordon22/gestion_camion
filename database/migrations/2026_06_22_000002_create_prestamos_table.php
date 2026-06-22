<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion');
            $table->string('categoria')->default('camion'); // camion, personal
            $table->decimal('monto_total', 12, 2);
            $table->integer('cantidad_cuotas');
            $table->decimal('valor_cuota', 12, 2);
            $table->unsignedTinyInteger('dia_vencimiento');
            $table->date('fecha_primera_cuota');
            $table->unsignedBigInteger('medio_pago_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
