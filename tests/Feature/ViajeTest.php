<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Viaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViajeTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create();
    }

    /**
     * La migración de camiones inserta un "CAMION-1" por defecto para agrupar
     * los registros viejos, así que lo sacamos para quedarnos con uno solo.
     */
    private function camion(): Camion
    {
        Camion::query()->delete();

        return Camion::create(['patente' => 'AB123CD', 'activo' => true]);
    }

    public function test_el_listado_de_viajes_se_muestra(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('viajes.index'))
            ->assertOk()
            ->assertSee('Viajes');
    }

    public function test_el_formulario_de_alta_ofrece_las_dos_formas_de_cobro(): void
    {
        $this->camion();

        $this->actingAs($this->usuario())
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('¿Cómo cobrás este viaje?', false)
            ->assertSee('Monto fijo por el viaje')
            ->assertSee('Por cantidad')
            ->assertSee('Origen')
            ->assertSee('Km recorridos');
    }

    public function test_con_un_solo_camion_el_selector_va_oculto(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('<input type="hidden" name="camion_id" value="' . $camion->id . '">', false)
            ->assertDontSee('— Seleccionar —', false);
    }

    public function test_el_formulario_de_edicion_trae_los_datos_del_viaje(): void
    {
        $camion = $this->camion();
        $viaje = Viaje::create([
            'camion_id'       => $camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-08-17 09:30',
            'cantidad'        => 800,
            'unidad'          => 'bolsas',
            'precio_unitario' => 280,
            'total'           => 224000,
            'destino'         => 'Salta',
        ]);

        $this->actingAs($this->usuario())
            ->get(route('viajes.edit', $viaje))
            ->assertOk()
            ->assertSee('value="800"', false)
            ->assertSee('value="Salta"', false);
    }

    public function test_se_puede_cargar_un_viaje_de_monto_fijo(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-08-17T09:30',
            'total'      => 150000,
            'origen'     => 'Tucumán',
            'destino'    => 'Salta',
        ])->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();

        $this->assertSame('fijo', $viaje->modo_cobro);
        $this->assertEquals(150000, $viaje->total);
        $this->assertNull($viaje->cantidad);
        $this->assertNull($viaje->unidad);
        $this->assertNull($viaje->precio_unitario);
        $this->assertSame('Tucumán → Salta', $viaje->ruta());
        $this->assertSame('Monto fijo', $viaje->resumenCarga());
    }

    public function test_el_total_por_cantidad_lo_calcula_el_servidor(): void
    {
        $camion = $this->camion();

        // El total que manda el navegador tiene que ser ignorado.
        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'       => $camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-08-17T09:30',
            'cantidad'        => 28.5,
            'unidad'          => 'toneladas',
            'precio_unitario' => 12000,
            'total'           => 1,
        ])->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();

        $this->assertEquals(342000, $viaje->total);
        $this->assertSame('28,5 toneladas', $viaje->resumenCarga());
    }

    public function test_por_cantidad_exige_cantidad_unidad_y_precio(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'cantidad',
            'fecha'      => '2026-08-17T09:30',
        ])->assertSessionHasErrors(['cantidad', 'unidad', 'precio_unitario']);

        $this->assertSame(0, Viaje::count());
    }

    public function test_monto_fijo_exige_el_total(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-08-17T09:30',
        ])->assertSessionHasErrors('total');
    }

    public function test_se_puede_marcar_un_viaje_como_cobrado(): void
    {
        $camion = $this->camion();
        $viaje = Viaje::create([
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-08-17 09:30',
            'total'      => 100000,
            'cobrado'    => false,
        ]);

        $this->actingAs($this->usuario())
            ->patchJson(route('viajes.cobrado', $viaje))
            ->assertOk()
            ->assertJson(['cobrado' => true, 'total' => 100000]);

        $this->assertTrue($viaje->fresh()->cobrado);
    }
}
