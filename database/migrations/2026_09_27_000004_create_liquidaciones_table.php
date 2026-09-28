<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que se le paga al chofer cada tanto: sus comisiones, menos lo que
     * pidió adelantado, más lo que puso de su bolsillo.
     *
     * La liquidación guarda los montos de ese día. Si después se corrige un
     * viaje, lo que se le pagó sigue siendo lo que se le pagó.
     *
     * chofer_movimientos son los adelantos y los gastos que pagó él. Quedan
     * sueltos hasta que entran en una liquidación.
     */
    public function up(): void
    {
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('choferes')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('comisiones', 12, 2)->default(0);
            $table->decimal('adelantos', 12, 2)->default(0);
            $table->decimal('gastos', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('chofer_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chofer_id')->constrained('choferes')->restrictOnDelete();
            $table->foreignId('camion_id')->nullable()->constrained('camiones')->nullOnDelete();
            $table->string('tipo', 20);
            $table->date('fecha');
            $table->decimal('monto', 12, 2);
            $table->string('concepto', 150)->nullable();
            $table->foreignId('liquidacion_id')->nullable()->constrained('liquidaciones')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chofer_movimientos');
        Schema::dropIfExists('liquidaciones');
    }
};
