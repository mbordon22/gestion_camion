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
            $this->cuenta = Cuenta::create(['nombre' => 'Cuenta de prueba', 'activa' => true]);
            CuentaActual::porDefecto($this->cuenta->id);
        }
    }

    protected function tearDown(): void
    {
        CuentaActual::porDefecto(null);

        parent::tearDown();
    }
}
