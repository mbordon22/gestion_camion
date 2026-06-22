<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->dateTime('fecha')->change();
            $table->string('tipo_ingreso')->nullable()->after('nro_ingreso');
            $table->string('motivo')->nullable()->after('tipo_ingreso');
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->date('fecha')->change();
            $table->dropColumn(['tipo_ingreso', 'motivo']);
        });
    }
};
