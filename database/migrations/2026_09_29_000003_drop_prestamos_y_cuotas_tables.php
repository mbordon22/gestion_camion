<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Préstamos se sacó del sistema (era de uso personal) y sus tablas
     * quedaron vacías. Se borran ahora para que una cuenta nueva no arrastre
     * tablas que nadie usa ni filtra por cuenta.
     */
    public function up(): void
    {
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('prestamos');
    }

    public function down(): void
    {
        (require database_path('migrations/2026_06_22_000002_create_prestamos_table.php'))->up();
        (require database_path('migrations/2026_06_22_000003_create_cuotas_table.php'))->up();
    }
};
