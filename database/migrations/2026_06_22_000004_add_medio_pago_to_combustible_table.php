<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('combustible', function (Blueprint $table) {
            $table->unsignedBigInteger('medio_pago_id')->nullable()->after('lugar');
            $table->date('fecha_vencimiento')->nullable()->after('medio_pago_id');
        });

        // Backfill: registros previos se consideran cobro inmediato (fecha del gasto)
        DB::statement('UPDATE combustible SET fecha_vencimiento = fecha WHERE fecha_vencimiento IS NULL');
    }

    public function down(): void
    {
        Schema::table('combustible', function (Blueprint $table) {
            $table->dropColumn(['medio_pago_id', 'fecha_vencimiento']);
        });
    }
};
