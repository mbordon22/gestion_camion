<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Combustible;
use App\Models\MedioPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedioPagoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Camion $camion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        // La migración de camiones inserta un "CAMION-1" por defecto.
        Camion::query()->delete();
        $this->camion = Camion::create(['patente' => 'TWH728', 'activo' => true]);
    }

    private function carga(MedioPago $medio, array $datos = []): Combustible
    {
        return Combustible::create($datos + [
            'camion_id'         => $this->camion->id,
            'fecha'             => '2026-09-20',
            'litros'            => 200,
            'precio_litro'      => 1500,
            'total'             => 300000,
            'medio_pago_id'     => $medio->id,
            'fecha_vencimiento' => '2026-09-20',
        ]);
    }

    public function test_se_puede_crear_un_medio_que_descuenta_el_cliente(): void
    {
        $this->actingAs($this->usuario)->post(route('medios-pago.store'), [
            'nombre' => 'Descuento Ingenio la Corona',
            'tipo'   => 'descuento',
            'activo' => '1',
        ])->assertRedirect(route('medios-pago.index'));

        $medio = MedioPago::first();
        $this->assertSame('descuento', $medio->tipo);
        $this->assertTrue($medio->esDescuento());
        $this->assertFalse($medio->esCredito());

        // No es crédito: no tiene cierre ni vencimiento que calcular.
        $this->assertNull($medio->dia_cierre);
        $this->assertNull($medio->dia_vencimiento);
        $this->assertFalse($medio->requiereFechaPagoManual());
        $this->assertFalse($medio->puedeSugerirFecha());
    }

    public function test_un_tipo_inventado_se_rechaza(): void
    {
        $this->actingAs($this->usuario)->post(route('medios-pago.store'), [
            'nombre' => 'Trueque',
            'tipo'   => 'permuta',
        ])->assertSessionHasErrors('tipo');

        $this->assertSame(0, MedioPago::count());
    }

    public function test_el_combustible_descontado_no_aparece_en_pagos(): void
    {
        $descuento = MedioPago::create(['nombre' => 'Descuento Ingenio la Corona', 'tipo' => 'descuento', 'activo' => true]);
        $credito   = MedioPago::create(['nombre' => 'Cuenta Corriente', 'tipo' => 'credito', 'activo' => true]);

        // Los dos vencen en el futuro, para que Pagos los pueda listar.
        $this->carga($descuento, ['fecha_vencimiento' => today()->addMonth()->toDateString()]);
        $this->carga($credito, ['fecha_vencimiento' => today()->addMonth()->toDateString(), 'lugar' => 'YPF Ruta 9']);

        $this->actingAs($this->usuario)
            ->get(route('pagos.index'))
            ->assertOk()
            // El gasto a crédito sí es un egreso de caja futuro.
            ->assertSee('YPF Ruta 9')
            // El descontado no: nunca sale plata por él.
            ->assertDontSee('Descuento Ingenio la Corona');
    }

    public function test_el_combustible_descontado_si_cuenta_como_gasto_en_reportes(): void
    {
        $descuento = MedioPago::create(['nombre' => 'Descuento Ingenio la Corona', 'tipo' => 'descuento', 'activo' => true]);
        $this->carga($descuento, ['total' => 300000]);

        // Período explícito: en SQLite el cast 'date' guarda con hora y un
        // gasto de hoy queda justo fuera del borde del rango por defecto.
        $this->actingAs($this->usuario)
            ->get(route('reportes.index', ['periodo' => 'rango', 'desde' => '2026-09-01', 'hasta' => '2026-09-30']))
            ->assertOk()
            ->assertSee('$ 300.000,00');
    }

    public function test_el_formulario_de_combustible_ofrece_el_medio_y_avisa(): void
    {
        MedioPago::create(['nombre' => 'Descuento Ingenio la Corona', 'tipo' => 'descuento', 'activo' => true]);

        $this->actingAs($this->usuario)
            ->get(route('combustible.create'))
            ->assertOk()
            ->assertSee('data-tipo="descuento"', false)
            ->assertSee('(lo descuenta el cliente)')
            ->assertSee('el cliente te lo descuenta de la factura');
    }

    public function test_el_listado_de_medios_muestra_la_etiqueta_del_tipo(): void
    {
        MedioPago::create(['nombre' => 'Descuento Ingenio la Corona', 'tipo' => 'descuento', 'activo' => true]);

        $this->actingAs($this->usuario)
            ->get(route('medios-pago.index'))
            ->assertOk()
            ->assertSee('Lo descuenta el cliente');
    }

    public function test_los_medios_de_pago_estan_en_catalogos_y_no_hay_prestamos(): void
    {
        $this->actingAs($this->usuario)
            ->get(route('viajes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Catálogos', 'Medios de pago'])
            ->assertDontSee('Préstamos');

        // Estando en medios de pago, lo marcado en la barra es Catálogos.
        $this->actingAs($this->usuario)
            ->get(route('medios-pago.index'))
            ->assertOk()
            ->assertSee('bg-gray-100 font-medium text-blue-700', false);
    }
}
