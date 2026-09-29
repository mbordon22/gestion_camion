<?php

namespace Tests;

use App\Models\Cuenta;
use App\Support\CuentaActual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** La cuenta en la que caen los datos que crea cada test. */
    protected ?Cuenta $cuenta = null;

    /**
     * Los datos son de una cuenta (Models\Concerns\DeLaCuenta). Los tests
     * crean sus datos antes de loguearse, así que hay una cuenta por
     * defecto: la de los usuarios que arma la factory.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            // Con todo prendido: cada test de una función la prueba sin tener
            // que activarla. Los que prueban el sistema simple la apagan.
            $this->cuenta = Cuenta::create([
                'nombre'    => 'Cuenta de prueba',
                'activa'    => true,
                'funciones' => array_keys(Cuenta::FUNCIONES),
            ]);
            CuentaActual::porDefecto($this->cuenta->id);
        }
    }

    /** Apaga funciones de la cuenta de prueba, como desde Configuración. */
    protected function apagar(string ...$funciones): void
    {
        $this->cuenta->update(['funciones' => array_values(array_diff($this->cuenta->funciones ?? [], $funciones))]);

        // El usuario logueado pudo haber cargado su cuenta en un pedido anterior.
        auth()->user()?->unsetRelation('cuenta');
    }

    protected function tearDown(): void
    {
        CuentaActual::porDefecto(null);

        parent::tearDown();
    }
}
