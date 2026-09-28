<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\ChoferMovimiento;
use App\Models\Equipo;
use App\Models\Liquidacion;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComisionChoferTest extends TestCase
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

    private function chofer(array $datos = []): Chofer
    {
        return Chofer::create($datos + [
            'nombre'    => 'Antonio',
            'telefono'  => '381 526-1730',
            'modalidad' => 'porcentaje',
            'valor'     => 15,
        ]);
    }

    /** Un viaje de $ 200.000 bruto con monto fijo. */
    private function datosViaje(array $datos = []): array
    {
        return $datos + [
            'camion_id'  => $this->camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now()->format('Y-m-d\TH:i'),
            'total'      => 200000,
        ];
    }

    private function viaje(array $datos = []): Viaje
    {
        return Viaje::create($datos + [
            'camion_id'  => $this->camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now(),
            'total'      => 100000,
            'cobrado'    => false,
        ]);
    }

    public function test_se_carga_como_cobra_el_chofer(): void
    {
        $this->actingAs($this->usuario)->post(route('choferes.store'), [
            'nombre'    => 'Antonio',
            'modalidad' => 'porcentaje',
            'valor'     => '15',
            'activo'    => '1',
        ])->assertRedirect(route('choferes.index'));

        $this->assertSame('15% de cada viaje', Chofer::first()->condicion());

        $this->actingAs($this->usuario)->post(route('choferes.store'), [
            'nombre'    => 'Sin valor',
            'modalidad' => 'porcentaje',
        ])->assertSessionHasErrors('valor');

        $this->actingAs($this->usuario)->post(route('choferes.store'), [
            'nombre'    => 'Dueño',
            'modalidad' => '',
            'valor'     => '15',
        ])->assertRedirect(route('choferes.index'));

        $duenio = Chofer::where('nombre', 'Dueño')->first();
        $this->assertNull($duenio->valor);
        $this->assertSame('Sin comisión', $duenio->condicion());
    }

    public function test_el_viaje_graba_la_comision_sobre_el_bruto_junto_con_el_alquiler(): void
    {
        $chofer = $this->chofer();
        $cisterna = Equipo::create([
            'nombre' => 'Cisterna', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25,
        ]);

        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje(['chofer_id' => $chofer->id, 'equipo_id' => $cisterna->id]))
            ->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();
        $this->assertEquals(15, $viaje->comision_porcentaje);
        $this->assertEquals(30000, $viaje->comision_monto);   // 15% de 200.000
        $this->assertEquals(50000, $viaje->alquiler_monto);   // 25% de 200.000
        $this->assertEquals(120000, $viaje->netoCamion());
        $this->assertNull($viaje->liquidacion_id);
    }

    public function test_chofer_sin_comision_o_viaje_sin_chofer_no_descuentan(): void
    {
        $duenio = $this->chofer(['nombre' => 'Dueño', 'modalidad' => null, 'valor' => null]);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['chofer_id' => $duenio->id]));
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje());
        // Chofer creado al vuelo: todavía no tiene acuerdo.
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje([
            'chofer_id' => 'nuevo', 'chofer_nuevo' => 'Nuevo',
        ]));

        $this->assertSame(3, Viaje::count());
        $this->assertSame(0, Viaje::whereNotNull('comision_monto')->count());
    }

    public function test_cambiar_el_acuerdo_no_toca_los_viajes_ya_cargados(): void
    {
        $chofer = $this->chofer();
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['chofer_id' => $chofer->id]));
        $viaje = Viaje::first();

        $chofer->update(['valor' => 20]);

        // Se corrige el total: el monto se recalcula con el 15% de cuando se cargó.
        $this->actingAs($this->usuario)
            ->put(route('viajes.update', $viaje), $this->datosViaje(['chofer_id' => $chofer->id, 'total' => 300000]))
            ->assertRedirect(route('viajes.index'));

        $viaje->refresh();
        $this->assertEquals(15, $viaje->comision_porcentaje);
        $this->assertEquals(45000, $viaje->comision_monto);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['chofer_id' => $chofer->id]));
        $this->assertEquals(20, Viaje::latest('id')->first()->comision_porcentaje);
    }

    public function test_un_viaje_liquidado_conserva_lo_que_se_le_pago_y_si_cambia_el_chofer_sale_de_la_liquidacion(): void
    {
        $chofer = $this->chofer();
        $otro = $this->chofer(['nombre' => 'Otro', 'valor' => 10]);
        $liquidacion = Liquidacion::create(['chofer_id' => $chofer->id, 'fecha' => '2026-09-15', 'comisiones' => 15000, 'total' => 15000]);
        $viaje = $this->viaje([
            'chofer_id' => $chofer->id, 'total' => 100000,
            'comision_porcentaje' => 15, 'comision_monto' => 15000, 'liquidacion_id' => $liquidacion->id,
        ]);

        $this->actingAs($this->usuario)
            ->put(route('viajes.update', $viaje), $this->datosViaje(['chofer_id' => $chofer->id, 'total' => 120000]))
            ->assertRedirect(route('viajes.index'));

        $viaje->refresh();
        $this->assertEquals(15000, $viaje->comision_monto);
        $this->assertSame($liquidacion->id, $viaje->liquidacion_id);

        $this->actingAs($this->usuario)
            ->put(route('viajes.update', $viaje), $this->datosViaje(['chofer_id' => $otro->id, 'total' => 120000]))
            ->assertRedirect(route('viajes.index'));

        $viaje->refresh();
        $this->assertEquals(12000, $viaje->comision_monto);
        $this->assertNull($viaje->liquidacion_id);
    }

    public function test_la_liquidacion_descuenta_adelantos_y_devuelve_gastos(): void
    {
        $chofer = $this->chofer();
        $otro = $this->chofer(['nombre' => 'Otro']);

        $a = $this->viaje(['chofer_id' => $chofer->id, 'fecha' => '2026-09-05', 'comision_monto' => 30000]);
        $b = $this->viaje(['chofer_id' => $chofer->id, 'fecha' => '2026-09-20', 'comision_monto' => 45000]);
        $ajeno = $this->viaje(['chofer_id' => $otro->id, 'fecha' => '2026-09-05', 'comision_monto' => 10000]);

        $this->actingAs($this->usuario)->post(route('choferes.movimientos.store', $chofer), [
            'tipo' => 'adelanto', 'fecha' => '2026-09-08', 'monto' => 20000, 'concepto' => 'A cuenta',
        ])->assertRedirect(route('choferes.liquidacion', $chofer));

        $this->actingAs($this->usuario)->post(route('choferes.movimientos.store', $chofer), [
            'tipo' => 'gasto', 'fecha' => '2026-09-09', 'monto' => 5000, 'concepto' => 'Gomería',
        ])->assertRedirect(route('choferes.liquidacion', $chofer));

        $adelanto = ChoferMovimiento::where('tipo', 'adelanto')->first();
        $gasto = ChoferMovimiento::where('tipo', 'gasto')->first();
        $this->assertNull($adelanto->camion_id);
        $this->assertSame($this->camion->id, $gasto->camion_id); // el del último viaje del chofer

        // Le debe 30.000 + 45.000 − 20.000 + 5.000.
        $this->actingAs($this->usuario)->get(route('choferes.liquidacion', $chofer))
            ->assertOk()
            ->assertSee('60.000,00')
            ->assertSee('Gomería');

        // Liquida la primera quincena: el viaje del 20 queda para la próxima.
        $this->actingAs($this->usuario)->post(route('choferes.liquidacion.store', $chofer), [
            'fecha'       => '2026-09-16',
            'viajes'      => [$a->id, $ajeno->id],
            'movimientos' => [$adelanto->id, $gasto->id],
        ])->assertRedirect();

        $liquidacion = Liquidacion::first();
        $this->assertEquals(30000, $liquidacion->comisiones);
        $this->assertEquals(20000, $liquidacion->adelantos);
        $this->assertEquals(5000, $liquidacion->gastos);
        $this->assertEquals(15000, $liquidacion->total);

        $this->assertSame($liquidacion->id, $a->fresh()->liquidacion_id);
        $this->assertNull($b->fresh()->liquidacion_id);
        $this->assertNull($ajeno->fresh()->liquidacion_id);
        $this->assertSame($liquidacion->id, $adelanto->fresh()->liquidacion_id);

        $this->actingAs($this->usuario)->get(route('liquidaciones.show', $liquidacion))
            ->assertOk()
            ->assertSee('15.000,00')
            ->assertSee('wa.me/5493815261730', false);

        // Un movimiento liquidado no se borra suelto.
        $this->actingAs($this->usuario)->delete(route('choferes.movimientos.destroy', $adelanto));
        $this->assertNotNull($adelanto->fresh());

        // Cargada por error: se deshace y todo vuelve a quedar pendiente.
        $this->actingAs($this->usuario)->delete(route('liquidaciones.destroy', $liquidacion))
            ->assertRedirect(route('choferes.liquidacion', $chofer));

        $this->assertSame(0, Liquidacion::count());
        $this->assertNull($a->fresh()->liquidacion_id);
        $this->assertNull($adelanto->fresh()->liquidacion_id);
    }

    public function test_no_se_liquida_si_los_adelantos_superan_lo_que_se_le_debe(): void
    {
        $chofer = $this->chofer();
        $viaje = $this->viaje(['chofer_id' => $chofer->id, 'comision_monto' => 10000]);
        $adelanto = $chofer->movimientos()->create(['tipo' => 'adelanto', 'fecha' => now(), 'monto' => 25000]);

        $this->actingAs($this->usuario)->post(route('choferes.liquidacion.store', $chofer), [
            'fecha'       => now()->toDateString(),
            'viajes'      => [$viaje->id],
            'movimientos' => [$adelanto->id],
        ])->assertSessionHasErrors('viajes');

        $this->actingAs($this->usuario)->post(route('choferes.liquidacion.store', $chofer), [
            'fecha' => now()->toDateString(),
        ])->assertSessionHasErrors('viajes');

        $this->assertSame(0, Liquidacion::count());
    }

    public function test_la_rentabilidad_resta_comisiones_y_gastos_del_chofer_pero_no_adelantos(): void
    {
        $chofer = $this->chofer();
        $this->viaje(['chofer_id' => $chofer->id, 'total' => 400000, 'comision_porcentaje' => 15, 'comision_monto' => 60000]);
        $chofer->movimientos()->create(['tipo' => 'gasto', 'fecha' => now(), 'monto' => 10000, 'camion_id' => $this->camion->id]);
        $chofer->movimientos()->create(['tipo' => 'adelanto', 'fecha' => now(), 'monto' => 50000]);

        $this->actingAs($this->usuario)->get(route('inicio'))
            ->assertOk()
            ->assertSee('330.000,00');     // 400.000 − 60.000 − 10.000

        $this->actingAs($this->usuario)->get(route('reportes.index', ['periodo' => 'mes']))
            ->assertOk()
            ->assertViewHas('totalComision', 60000)
            ->assertViewHas('totalGastosChofer', 10000)
            ->assertViewHas('resultado', 330000)
            ->assertSee('Choferes a comisión');

        // En Pagos: 60.000 − 50.000 + 10.000.
        $this->actingAs($this->usuario)->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Choferes sin liquidar')
            ->assertSee('20.000,00');

        $this->actingAs($this->usuario)->get(route('choferes.index'))
            ->assertOk()
            ->assertSee('15% de cada viaje')
            ->assertSee('20.000,00');
    }

    public function test_el_formulario_del_viaje_lleva_el_acuerdo_del_chofer(): void
    {
        $chofer = $this->chofer();

        $this->actingAs($this->usuario)->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('data-modalidad="porcentaje"', false)
            ->assertSee('data-valor="15.00"', false);
    }

    public function test_el_whatsapp_se_arma_con_el_codigo_de_pais(): void
    {
        $this->assertSame('5493815261730', (new Chofer(['telefono' => '381 526-1730']))->whatsapp());
        $this->assertSame('5493815261730', (new Chofer(['telefono' => '+54 9 381 526 1730']))->whatsapp());
        $this->assertSame('5493815261730', (new Chofer(['telefono' => '0381-5261730']))->whatsapp());
        $this->assertNull((new Chofer(['telefono' => '4261730']))->whatsapp());
        $this->assertNull((new Chofer())->whatsapp());
    }
}
