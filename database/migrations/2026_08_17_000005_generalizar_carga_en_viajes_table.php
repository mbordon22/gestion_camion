<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Hasta acá un viaje era siempre "N bolsas x $precio": el que cobraba un flete
     * fijo o llevaba otra cosa no podía cargarlo. Ahora hay dos formas de cobro:
     *   - 'fijo'     -> el usuario escribe el total del viaje
     *   - 'cantidad' -> cantidad x precio_unitario, con la unidad que corresponda
     *     (bolsas, toneladas, pallets, cabezas, ...)
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->string('modo_cobro', 10)->default('cantidad')->after('camion_id');
        });

        // El rename y el cambio de tipo van en bloques separados: MySQL no los
        // resuelve bien si se mezclan en el mismo closure.
        Schema::table('viajes', function (Blueprint $table) {
            $table->renameColumn('bolsas', 'cantidad');
            $table->renameColumn('precio_bolsa', 'precio_unitario');
        });

        // cantidad pasa a decimal (toneladas con coma) y ambas admiten NULL,
        // porque en modo 'fijo' no se cargan.
        Schema::table('viajes', function (Blueprint $table) {
            $table->decimal('cantidad', 10, 2)->nullable()->change();
            $table->decimal('precio_unitario', 12, 2)->nullable()->change();
        });

        Schema::table('viajes', function (Blueprint $table) {
            $table->string('unidad', 20)->nullable()->after('cantidad');
            $table->string('origen', 100)->nullable()->after('total');
            $table->unsignedInteger('km_recorridos')->nullable()->after('destino');
        });

        // Todo lo cargado hasta hoy era azúcar en bolsas.
        DB::table('viajes')->update([
            'modo_cobro' => 'cantidad',
            'unidad'     => 'bolsas',
        ]);
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropColumn(['modo_cobro', 'unidad', 'origen', 'km_recorridos']);
        });

        Schema::table('viajes', function (Blueprint $table) {
            $table->renameColumn('cantidad', 'bolsas');
            $table->renameColumn('precio_unitario', 'precio_bolsa');
        });

        // Los viajes en modo 'fijo' no tienen cantidad: sin un valor no se puede
        // volver a NOT NULL, así que se les pone 0.
        DB::table('viajes')->whereNull('bolsas')->update(['bolsas' => 0]);
        DB::table('viajes')->whereNull('precio_bolsa')->update(['precio_bolsa' => 0]);

        Schema::table('viajes', function (Blueprint $table) {
            $table->integer('bolsas')->nullable(false)->change();
            $table->decimal('precio_bolsa', 10, 2)->nullable(false)->change();
        });
    }
};
