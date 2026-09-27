<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El cliente es opcional en el viaje para no trabar una carga, pero todos
     * los viajes cargados hasta ahora fueron para Control Union: se crea ese
     * cliente y se les asigna, así los informes por cliente arrancan con datos.
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('camion_id')
                ->constrained('clientes')->restrictOnDelete();
        });

        if (! DB::table('viajes')->exists()) {
            return;
        }

        $clienteId = DB::table('clientes')->where('nombre', 'Control Union')->value('id')
            ?? DB::table('clientes')->insertGetId(['nombre' => 'Control Union', 'activo' => true, 'created_at' => now()]);

        DB::table('viajes')->whereNull('cliente_id')->update(['cliente_id' => $clienteId]);
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
        });
    }
};
