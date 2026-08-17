<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Facturar y cobrar no son lo mismo, y al dueño del camión le importa
     * si cobró. El switch pasa a llamarse "Cobrado".
     */
    public function up(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->renameColumn('facturado', 'cobrado');
        });
    }

    public function down(): void
    {
        Schema::table('viajes', function (Blueprint $table) {
            $table->renameColumn('cobrado', 'facturado');
        });
    }
};
