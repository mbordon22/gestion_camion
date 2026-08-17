<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camiones', function (Blueprint $table) {
            $table->id();
            $table->string('patente')->unique();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Camión por defecto para asociar los registros ya existentes de viajes,
        // combustible y mantenimiento (cargados antes de que existiera este ABM).
        DB::table('camiones')->insert([
            'patente' => 'CAMION-1',
            'activo' => true,
            'observaciones' => 'Creado automáticamente para agrupar los registros cargados antes del ABM de camiones. Editá la patente y los datos reales.',
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('camiones');
    }
};
