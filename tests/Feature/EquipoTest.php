<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Equipo;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipoTest extends TestCase
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

    private function cisterna(array $datos = []): Equipo
    {
        return Equipo::create($datos + [
            'nombre'      => 'Cisterna',
            'camion_id'   => $this->camion->id,
            'alquilado'   => true,
            'propietario' => 'Pérez',
            'modalidad'   => 'porcentaje',
            'valor'       => 25,
        ]);
    }

    /** Un viaje de vinaza de 27,7 t a $ 10.000 la tonelada: $ 277.000 bruto. */
    private function datosViaje(array $datos = []): array
    {
        return $datos + [
            'camion_id'       => $this->camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => now()->format('Y-m-d\TH:i'),
            'producto'        => 'Vinaza',
            'cantidad'        => 27.7,
            'unidad'          => 'toneladas',
            'precio_unitario' => 10000,
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

    public function test_se_puede_crear_un_equipo_alquilado_a_porcentaje(): void
    {
        $this->actingAs($this->usuario)->post(route('equipos.store'), [
            'nombre'      => 'Cisterna vinaza',
            'tipo'        => 'Cisterna',
            'patente'     => 'ab 123 cd',
            'camion_id'   => $this->camion->id,
            'alquilado'   => '1',
            'propietario' => 'Pérez',
            'modalidad'   => 'porcentaje',
            'valor'       => '25',
            'activo'      => '1',
        ])->assertRedirect(route('equipos.index'));

        $equipo = Equipo::first();
        $this->assertTrue($equipo->alquilado);
        $this->assertSame('AB123CD', $equipo->patente);
        $this->assertSame('25% de cada viaje', $equipo->condicion());
    }

    public function test_un_equipo_propio_no_guarda_acuerdo_con_nadie(): void
    {
        $this->actingAs($this->usuario)->post(route('equipos.store'), [
            'nombre'      => 'Batea',
            'alquilado'   => '0',
            'propietario' => 'Quedó escrito de antes',
            'modalidad'   => 'porcentaje',
            'valor'       => '25',
        ])->assertRedirect(route('equipos.index'));

        $equipo = Equipo::first();
        $this->assertFalse($equipo->alquilado);
        $this->assertNull($equipo->propietario);
        $this->assertNull($equipo->valor);
        $this->assertSame('Propio', $equipo->condicion());
    }

    public function test_alquilado_exige_el_acuerdo_y_el_porcentaje_no_pasa_de_100(): void
    {
        $this->actingAs($this->usuario)->post(route('equipos.store'), [
            'nombre'    => 'Cisterna',
            'alquilado' => '1',
            'modalidad' => 'porcentaje',
        ])->assertSessionHasErrors('valor');

        $this->actingAs($this->usuario)->post(route('equipos.store'), [
            'nombre'    => 'Cisterna',
            'alquilado' => '1',
            'modalidad' => 'porcentaje',
            'valor'     => '125',
        ])->assertSessionHasErrors('valor');

        $this->assertSame(0, Equipo::count());
    }

    public function test_el_viaje_con_equipo_alquilado_graba_la_parte_del_dueño_sobre_el_bruto(): void
    {
        $cisterna = $this->cisterna();

        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje(['equipo_id' => $cisterna->id]))
            ->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();
        $this->assertEquals(277000, $viaje->total);
        $this->assertEquals(25, $viaje->alquiler_porcentaje);
        $this->assertEquals(69250, $viaje->alquiler_monto);
        $this->assertEquals(207750, $viaje->netoCamion());
        $this->assertNull($viaje->alquiler_pagado_el);
    }

    public function test_con_equipo_propio_o_sin_equipo_no_hay_alquiler(): void
    {
        $batea = Equipo::create(['nombre' => 'Batea', 'alquilado' => false]);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['equipo_id' => $batea->id]));
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje());

        $this->assertSame(2, Viaje::count());
        $this->assertSame(0, Viaje::whereNotNull('alquiler_monto')->count());
    }

    public function test_el_monto_fijo_por_viaje_no_depende_del_total(): void
    {
        $equipo = $this->cisterna(['modalidad' => 'fijo_viaje', 'valor' => 20000]);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['equipo_id' => $equipo->id]));

        $viaje = Viaje::first();
        $this->assertNull($viaje->alquiler_porcentaje);
        $this->assertEquals(20000, $viaje->alquiler_monto);
    }

    public function test_cambiar_el_acuerdo_no_toca_los_viajes_ya_cargados(): void
    {
        $cisterna = $this->cisterna();
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['equipo_id' => $cisterna->id]));
        $viaje = Viaje::first();

        $cisterna->update(['valor' => 20]);

        // Se corrige el peso del ticket: el monto se recalcula, pero con el 25%
        // que regía cuando se cargó el viaje.
        $this->actingAs($this->usuario)
            ->put(route('viajes.update', $viaje), $this->datosViaje(['equipo_id' => $cisterna->id, 'cantidad' => 30]))
            ->assertRedirect(route('viajes.index'));

        $viaje->refresh();
        $this->assertEquals(25, $viaje->alquiler_porcentaje);
        $this->assertEquals(75000, $viaje->alquiler_monto);

        // Un viaje nuevo ya va con el 20%.
        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje(['equipo_id' => $cisterna->id]));
        $this->assertEquals(20, Viaje::latest('id')->first()->alquiler_porcentaje);
    }

    public function test_si_cambia_el_equipo_el_pago_al_dueño_anterior_no_queda_en_el_viaje(): void
    {
        $cisterna = $this->cisterna();
        $otra = $this->cisterna(['nombre' => 'Cisterna prestada', 'valor' => 30]);

        $viaje = $this->viaje([
            'equipo_id' => $cisterna->id, 'total' => 100000,
            'alquiler_porcentaje' => 25, 'alquiler_monto' => 25000, 'alquiler_pagado_el' => '2026-09-25',
        ]);

        $this->actingAs($this->usuario)->put(route('viajes.update', $viaje), [
            'camion_id'  => $this->camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now()->format('Y-m-d\TH:i'),
            'total'      => 100000,
            'equipo_id'  => $otra->id,
        ])->assertRedirect(route('viajes.index'));

        $viaje->refresh();
        $this->assertEquals(30000, $viaje->alquiler_monto);
        $this->assertNull($viaje->alquiler_pagado_el);
    }

    public function test_el_alta_propone_el_equipo_del_ultimo_viaje_y_repetir_lo_trae(): void
    {
        $cisterna = $this->cisterna();
        $viaje = $this->viaje(['equipo_id' => $cisterna->id]);

        $this->actingAs($this->usuario)->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('value="' . $cisterna->id . '"', false)
            ->assertViewHas('equipoSugerido', $cisterna->id);

        $this->actingAs($this->usuario)->get(route('viajes.create', ['repetir' => $viaje->id]))
            ->assertOk()
            ->assertViewHas('viaje', fn ($repetido) => $repetido->equipo_id === $cisterna->id);
    }

    public function test_el_resultado_descuenta_la_parte_del_dueño(): void
    {
        $cisterna = $this->cisterna();
        $this->viaje(['equipo_id' => $cisterna->id, 'total' => 400000, 'alquiler_porcentaje' => 25, 'alquiler_monto' => 100000]);

        $this->actingAs($this->usuario)->get(route('inicio'))
            ->assertOk()
            ->assertSee('300.000,00')      // 400.000 − 100.000 del dueño
            ->assertSee('Alquiler de equipos');

        $this->actingAs($this->usuario)->get(route('reportes.index', ['periodo' => 'mes']))
            ->assertOk()
            ->assertViewHas('totalAlquiler', 100000)
            ->assertViewHas('resultado', 300000)
            ->assertSee('Equipos alquilados')
            ->assertSee('Pérez');
    }

    public function test_registrar_el_pago_al_dueño_marca_solo_sus_viajes_pendientes(): void
    {
        $cisterna = $this->cisterna();
        $otra = $this->cisterna(['nombre' => 'Otra cisterna']);

        $a = $this->viaje(['equipo_id' => $cisterna->id, 'alquiler_monto' => 25000]);
        $b = $this->viaje(['equipo_id' => $cisterna->id, 'alquiler_monto' => 30000]);
        $ajeno = $this->viaje(['equipo_id' => $otra->id, 'alquiler_monto' => 10000]);

        $this->actingAs($this->usuario)->get(route('pagos.index'))
            ->assertOk()
            ->assertSee('Alquiler de equipos sin pagar')
            ->assertSee('65.000,00');

        $this->actingAs($this->usuario)->post(route('equipos.pagos.store', $cisterna), [
            'fecha'  => '2026-09-30',
            'viajes' => [$a->id, $ajeno->id],
        ])->assertRedirect(route('equipos.pagos', $cisterna));

        $this->assertSame('2026-09-30', $a->fresh()->alquiler_pagado_el->toDateString());
        $this->assertNull($b->fresh()->alquiler_pagado_el);
        $this->assertNull($ajeno->fresh()->alquiler_pagado_el);

        $this->actingAs($this->usuario)->get(route('equipos.pagos', $cisterna))
            ->assertOk()
            ->assertSee('30/09/2026')
            ->assertSee('30.000,00');

        // Cargado por error: se deshace y vuelve a deberse.
        $this->actingAs($this->usuario)->delete(route('equipos.pagos.destroy', [$cisterna, '2026-09-30']))
            ->assertRedirect(route('equipos.pagos', $cisterna));

        $this->assertNull($a->fresh()->alquiler_pagado_el);
    }

    public function test_el_listado_muestra_lo_que_se_le_debe_a_cada_dueño(): void
    {
        $cisterna = $this->cisterna();
        $this->viaje(['equipo_id' => $cisterna->id, 'alquiler_monto' => 25000]);
        $this->viaje(['equipo_id' => $cisterna->id, 'alquiler_monto' => 30000, 'alquiler_pagado_el' => '2026-09-20']);

        $this->actingAs($this->usuario)->get(route('equipos.index'))
            ->assertOk()
            ->assertSee('Cisterna')
            ->assertSee('25% de cada viaje')
            ->assertSee('25.000,00')
            ->assertDontSee('55.000,00');
    }

    public function test_un_equipo_con_viajes_se_desactiva_en_vez_de_borrarse(): void
    {
        $cisterna = $this->cisterna();
        $this->viaje(['equipo_id' => $cisterna->id]);

        $this->actingAs($this->usuario)->delete(route('equipos.destroy', $cisterna))
            ->assertRedirect(route('equipos.index'));

        $this->assertFalse($cisterna->fresh()->activo);
    }
}
