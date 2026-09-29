<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Combustible;
use App\Models\Cuenta;
use App\Models\Equipo;
use App\Models\MedioPago;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo avanzado se prende por cuenta (Cuenta::FUNCIONES). Apagado no se ve,
 * pero lo que ya estaba cargado no se toca.
 */
class FuncionesTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Camion $camion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();
        Camion::query()->delete();
        $this->camion = Camion::create(['patente' => 'AB123CD', 'activo' => true]);
    }

    private function todoApagado(): void
    {
        $this->apagar(...array_keys(Cuenta::FUNCIONES));
    }

    // --- Lo que se ve ----------------------------------------------------------

    public function test_con_todo_apagado_el_sistema_es_el_simple(): void
    {
        Chofer::create(['nombre' => 'Rivadeneira', 'activo' => true]);
        Equipo::create(['nombre' => 'Cisterna', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25]);
        $this->todoApagado();

        // El menú, sin Tarifas, Equipos ni Pagos.
        $this->actingAs($this->usuario)->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('href="' . route('tarifas.index') . '"', false)
            ->assertDontSee('href="' . route('equipos.index') . '"', false)
            ->assertDontSee(route('pagos.index'), false)
            ->assertSee(route('configuracion.edit'), false);

        // El viaje, sin N° de orden, equipo ni tarifa.
        $this->actingAs($this->usuario)->get(route('viajes.create'))
            ->assertOk()
            ->assertDontSee('name="nro_orden"', false)
            ->assertDontSee('name="equipo_id"', false)
            ->assertDontSee(route('tarifas.sugerir'), false)
            ->assertSee('data-modalidad=""', false);

        // El chofer, sin cómo cobra.
        $this->actingAs($this->usuario)->get(route('choferes.create'))
            ->assertDontSee('name="modalidad"', false);

        // Los medios de pago, sin crédito.
        $this->actingAs($this->usuario)->get(route('medios-pago.create'))
            ->assertDontSee('value="credito"', false);
    }

    public function test_con_todo_prendido_se_ve_todo(): void
    {
        Equipo::create(['nombre' => 'Cisterna', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25]);

        $this->actingAs($this->usuario)->get(route('inicio'))
            ->assertSee('href="' . route('tarifas.index') . '"', false)
            ->assertSee('href="' . route('equipos.index') . '"', false)
            ->assertSee(route('pagos.index'), false);

        $this->actingAs($this->usuario)->get(route('viajes.create'))
            ->assertSee('name="nro_orden"', false)
            ->assertSee('name="equipo_id"', false)
            ->assertSee(route('tarifas.sugerir'), false);

        $this->actingAs($this->usuario)->get(route('medios-pago.create'))
            ->assertSee('value="credito"', false);
    }

    public function test_pagos_aparece_con_cualquiera_de_lo_que_se_paga_despues(): void
    {
        $this->apagar('tarjetas', 'equipos');

        // Con choferes a comisión hay algo que pagar: Pagos está, sin los meses de las tarjetas.
        $this->actingAs($this->usuario)->get(route('inicio'))->assertSee(route('pagos.index'), false);
        $this->actingAs($this->usuario)->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Lo que tenés que pagar: choferes.')
            ->assertDontSee('A pagar el mes que viene');
    }

    // --- Configuración ---------------------------------------------------------

    public function test_cada_cuenta_elige_lo_que_usa(): void
    {
        $this->todoApagado();

        $this->actingAs($this->usuario)->get(route('configuracion.edit'))
            ->assertOk()
            ->assertSee('Tarifas por distancia')
            ->assertSee('Choferes a comisión');

        // Lo que no existe se descarta, y quedan en el orden de siempre.
        $this->actingAs($this->usuario)->put(route('configuracion.update'), [
            'funciones' => ['', 'equipos', 'cualquier-cosa', 'orden'],
        ])->assertRedirect(route('configuracion.edit'));

        $this->assertSame(['orden', 'equipos'], $this->cuenta->fresh()->funciones);

        $this->actingAs($this->usuario->fresh())->get(route('inicio'))
            ->assertSee('href="' . route('equipos.index') . '"', false)
            ->assertDontSee('href="' . route('tarifas.index') . '"', false);

        // Apagar todo: el formulario manda sólo el campo vacío.
        $this->actingAs($this->usuario)->put(route('configuracion.update'), ['funciones' => ['']]);
        $this->assertSame([], $this->cuenta->fresh()->funciones);
    }

    public function test_la_configuracion_es_de_la_propia_cuenta(): void
    {
        $otra = Cuenta::create(['nombre' => 'Transportes Pérez', 'activa' => true, 'funciones' => []]);
        $deOtra = User::factory()->create(['cuenta_id' => $otra->id]);

        $this->actingAs($deOtra)->put(route('configuracion.update'), ['funciones' => ['tarifas']]);

        $this->assertSame(['tarifas'], $otra->fresh()->funciones);
        $this->assertSame(array_keys(Cuenta::FUNCIONES), $this->cuenta->fresh()->funciones);
    }

    public function test_el_admin_elige_lo_que_usa_cada_cuenta(): void
    {
        $this->usuario->es_admin = true;
        $this->usuario->save();

        // Sin elegir nada, arranca simple.
        $this->actingAs($this->usuario)->post(route('admin.cuentas.store'), [
            'nombre' => 'Fletes Gómez', 'usuario' => 'Ana', 'email' => 'ana@gomez.com', 'password' => 'camion2026',
            'funciones' => [''],
        ]);
        $cuenta = Cuenta::where('nombre', 'Fletes Gómez')->firstOrFail();
        $this->assertSame([], $cuenta->funciones);

        $this->actingAs($this->usuario)->get(route('admin.cuentas.edit', $cuenta))
            ->assertSee('Qué usa')
            ->assertSee('Tarjetas de crédito');

        $this->actingAs($this->usuario)->put(route('admin.cuentas.update', $cuenta), [
            'nombre' => 'Fletes Gómez', 'activa' => '1', 'funciones' => ['', 'comisiones'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['comisiones'], $cuenta->fresh()->funciones);
    }

    // --- Apagado no toca lo cargado ----------------------------------------------

    public function test_editar_un_viaje_con_todo_apagado_no_borra_lo_que_tenia(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira', 'activo' => true, 'modalidad' => 'porcentaje', 'valor' => 15]);
        $equipo = Equipo::create(['nombre' => 'Cisterna', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25]);

        $this->actingAs($this->usuario)->post(route('viajes.store'), [
            'camion_id' => $this->camion->id, 'chofer_id' => $chofer->id, 'equipo_id' => $equipo->id,
            'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => '100.000', 'nro_orden' => '10110',
        ])->assertSessionHasNoErrors();

        $viaje = Viaje::firstOrFail();
        $this->assertEquals(25000, $viaje->alquiler_monto);
        $this->assertEquals(15000, $viaje->comision_monto);

        $this->todoApagado();

        // El formulario ya no manda orden ni equipo; se cambia el total.
        $this->actingAs($this->usuario)->put(route('viajes.update', $viaje), [
            'camion_id' => $this->camion->id, 'chofer_id' => $chofer->id,
            'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => '120.000',
        ])->assertSessionHasNoErrors();

        $viaje->refresh();
        $this->assertEquals(120000, $viaje->total);
        $this->assertSame('10110', $viaje->nro_orden);
        $this->assertSame($equipo->id, $viaje->equipo_id);
        $this->assertEquals(25000, $viaje->alquiler_monto);
        $this->assertEquals(15000, $viaje->comision_monto);
    }

    public function test_sin_comisiones_ni_equipos_un_viaje_nuevo_no_descuenta_nada(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira', 'activo' => true, 'modalidad' => 'porcentaje', 'valor' => 15]);
        $equipo = Equipo::create(['nombre' => 'Cisterna', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25]);
        $this->apagar('comisiones', 'equipos');

        // Aunque llegue un equipo (el formulario no lo muestra), no se usa.
        $this->actingAs($this->usuario)->post(route('viajes.store'), [
            'camion_id' => $this->camion->id, 'chofer_id' => $chofer->id, 'equipo_id' => $equipo->id,
            'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => '100.000',
        ])->assertSessionHasNoErrors();

        $viaje = Viaje::firstOrFail();
        $this->assertSame($chofer->id, $viaje->chofer_id);
        $this->assertNull($viaje->equipo_id);
        $this->assertNull($viaje->alquiler_monto);
        $this->assertNull($viaje->comision_monto);
    }

    public function test_sin_comisiones_cambiar_de_chofer_saca_la_comision_del_anterior(): void
    {
        $antonio = Chofer::create(['nombre' => 'Antonio', 'activo' => true, 'modalidad' => 'porcentaje', 'valor' => 15]);
        $otro = Chofer::create(['nombre' => 'Otro', 'activo' => true]);
        $viaje = Viaje::create([
            'camion_id' => $this->camion->id, 'chofer_id' => $antonio->id, 'modo_cobro' => 'fijo',
            'fecha' => '2026-09-20', 'total' => 100000, 'comision_porcentaje' => 15, 'comision_monto' => 15000,
        ]);
        $this->apagar('comisiones');

        $this->actingAs($this->usuario)->put(route('viajes.update', $viaje), [
            'camion_id' => $this->camion->id, 'chofer_id' => $otro->id,
            'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => '100.000',
        ])->assertSessionHasNoErrors();

        $this->assertNull($viaje->fresh()->comision_monto);
    }

    public function test_sin_comisiones_editar_un_chofer_conserva_su_acuerdo(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira', 'activo' => true, 'modalidad' => 'porcentaje', 'valor' => 15]);
        $this->apagar('comisiones');

        $this->actingAs($this->usuario)->put(route('choferes.update', $chofer), [
            'nombre' => 'Antonio Rivadeneira', 'activo' => '1',
        ])->assertSessionHasNoErrors();

        $chofer->refresh();
        $this->assertSame('Antonio Rivadeneira', $chofer->nombre);
        $this->assertSame('porcentaje', $chofer->modalidad);
        $this->assertEquals(15, $chofer->valor);
    }

    public function test_sin_tarjetas_la_fecha_de_pago_aparece_solo_para_un_medio_de_credito(): void
    {
        $efectivo = MedioPago::create(['nombre' => 'Efectivo', 'tipo' => 'efectivo', 'activo' => true]);
        $visa = MedioPago::create(['nombre' => 'Visa', 'tipo' => 'credito', 'activo' => true]);
        $this->apagar('tarjetas');

        $this->actingAs($this->usuario)->get(route('combustible.create'))
            ->assertSee('id="bloque-fecha-pago" data-solo-credito="1" class="hidden"', false);

        // Un gasto de contado se paga el mismo día.
        $this->actingAs($this->usuario)->post(route('combustible.store'), [
            'camion_id' => $this->camion->id, 'fecha' => '2026-09-20', 'litros' => 100, 'precio_litro' => 1500,
            'total' => 150000, 'medio_pago_id' => $efectivo->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-09-20', Combustible::firstOrFail()->fecha_vencimiento->toDateString());

        // Uno viejo con tarjeta sigue mostrando su fecha de pago.
        $conTarjeta = Combustible::create([
            'camion_id' => $this->camion->id, 'fecha' => '2026-09-20', 'litros' => 100, 'precio_litro' => 1500,
            'total' => 150000, 'medio_pago_id' => $visa->id, 'fecha_vencimiento' => '2026-10-10',
        ]);

        $this->actingAs($this->usuario)->get(route('combustible.edit', $conTarjeta))
            ->assertSee('id="bloque-fecha-pago" data-solo-credito="1" class=""', false)
            ->assertSee('value="2026-10-10"', false);
    }

    // --- Las cuentas que ya existían -------------------------------------------

    public function test_la_migracion_prende_lo_que_cada_cuenta_ya_usaba(): void
    {
        $vieja = Cuenta::create(['nombre' => 'Vieja', 'activa' => true]);
        $simple = Cuenta::create(['nombre' => 'Simple', 'activa' => true]);

        $this->enCuenta($vieja, function () {
            Equipo::create(['nombre' => 'Cisterna']);
            MedioPago::create(['nombre' => 'Visa', 'tipo' => 'credito', 'activo' => true]);
            Chofer::create(['nombre' => 'Antonio', 'activo' => true, 'modalidad' => 'porcentaje', 'valor' => 15]);
            $camion = Camion::create(['patente' => 'ZZ999ZZ', 'activo' => true]);
            Viaje::create(['camion_id' => $camion->id, 'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => 1, 'nro_orden' => '1']);
        });

        $migracion = require database_path('migrations/2026_09_29_000004_add_funciones_to_cuentas_table.php');
        $migracion->down();
        $migracion->up();

        $this->assertSame(['orden', 'equipos', 'comisiones', 'tarjetas'], $vieja->fresh()->funciones);
        $this->assertSame([], $simple->fresh()->funciones);
    }

    private function enCuenta(Cuenta $cuenta, \Closure $crear): void
    {
        \App\Support\CuentaActual::porDefecto($cuenta->id);

        try {
            $crear();
        } finally {
            \App\Support\CuentaActual::porDefecto($this->cuenta->id);
        }
    }
}
