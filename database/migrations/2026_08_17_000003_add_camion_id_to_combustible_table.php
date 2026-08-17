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
            $table->foreignId('camion_id')->nullable()->after('id')
                ->constrained('camiones')->restrictOnDelete();
        });

        $primerCamionId = DB::table('camiones')->orderBy('id')->value('id');
        DB::table('combustible')->whereNull('camion_id')->update(['camion_id' => $primerCamionId]);
    }

    public function down(): void
    {
        Schema::table('combustible', function (Blueprint $table) {
            $table->dropConstrainedForeignId('camion_id');
        });
    }
};
