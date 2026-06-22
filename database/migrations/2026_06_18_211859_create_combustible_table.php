<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combustible', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->decimal('litros', 8, 2);
            $table->decimal('precio_litro', 8, 2);
            $table->decimal('total', 10, 2);
            $table->integer('km_odometro')->nullable();
            $table->string('lugar')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combustible');
    }
};
