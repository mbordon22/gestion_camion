<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChoferTest extends TestCase
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

    private function datosViaje(array $datos = []): array
    {
        return $datos + [
            'camion_id'  => $this->camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now()->format('Y-m-d\TH:i'),
            'total'      => 150000,
        ];
    }

    public function test_se_puede_crear_un_chofer_y_el_dni_queda_con_puntos(): void
    {
        $this->actingAs($this->usuario)->post(route('choferes.store'), [
            'nombre' => 'Rivadeneira',
            'dni'    => '12345678',
            'activo' => '1',
        ])->assertRedirect(route('choferes.index'));

        $chofer = Chofer::first();
        $this->assertSame('Rivadeneira', $chofer->nombre);
        $this->assertSame('12.345.678', $chofer->dni);
        $this->assertTrue($chofer->activo);
    }

    public function test_no_se_repite_el_nombre_ni_se_acepta_un_dni_mal_escrito(): void
    {
        Chofer::create(['nombre' => 'Rivadeneira']);

        $this->actingAs($this->usuario)->post(route('choferes.store'), [
            'nombre' => 'Rivadeneira',
            'dni'    => '123',
        ])->assertSessionHasErrors(['nombre', 'dni']);

        $this->assertSame(1, Chofer::count());
    }

    public function test_el_listado_muestra_los_viajes_y_el_ultimo_de_cada_chofer(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira']);
        $this->viaje(['chofer_id' => $chofer->id, 'fecha' => '2026-09-15 10:00']);
        $this->viaje(['chofer_id' => $chofer->id, 'fecha' => '2026-09-20 18:47']);

        $this->actingAs($this->usuario)
            ->get(route('choferes.index'))
            ->assertOk()
            ->assertSee('Rivadeneira')
            ->assertSee('20/09/2026')
            ->assertSee('Ver viajes');
    }

    public function test_un_chofer_con_viajes_se_desactiva_en_vez_de_borrarse(): void
    {
        $conViajes = Chofer::create(['nombre' => 'Rivadeneira']);
        $sinViajes = Chofer::create(['nombre' => 'Suplente']);
        $this->viaje(['chofer_id' => $conViajes->id]);

        $this->actingAs($this->usuario)->delete(route('choferes.destroy', $conViajes));
        $this->actingAs($this->usuario)->delete(route('choferes.destroy', $sinViajes));

        $this->assertFalse($conViajes->fresh()->activo);
        $this->assertNull($sinViajes->fresh());
    }

    public function test_el_formulario_de_viaje_sugiere_el_chofer_del_ultimo_viaje(): void
    {
        Chofer::create(['nombre' => 'Suplente']);
        $ultimo = Chofer::create(['nombre' => 'Rivadeneira']);
        $this->viaje(['chofer_id' => $ultimo->id]);

        $this->actingAs($this->usuario)
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertViewHas('choferSugerido', $ultimo->id)
            ->assertSeeInOrder(['value="' . $ultimo->id . '"', 'selected>Rivadeneira</option>'], false)
            ->assertSee('+ Nuevo chofer…');
    }

    public function test_se_puede_crear_el_chofer_desde_el_formulario_de_viaje(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje([
                'chofer_id'    => 'nuevo',
                'chofer_nuevo' => 'Rivadeneira',
            ]))
            ->assertRedirect(route('viajes.index'));

        $chofer = Chofer::firstWhere('nombre', 'Rivadeneira');
        $this->assertNotNull($chofer);
        $this->assertSame($chofer->id, Viaje::first()->chofer_id);
    }

    public function test_chofer_nuevo_sin_nombre_no_se_guarda(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje(['chofer_id' => 'nuevo']))
            ->assertSessionHasErrors('chofer_nuevo');

        $this->assertSame(0, Viaje::count());
    }

    public function test_viajes_se_filtra_por_chofer(): void
    {
        $titular  = Chofer::create(['nombre' => 'Rivadeneira']);
        $suplente = Chofer::create(['nombre' => 'Suplente']);
        $this->viaje(['chofer_id' => $titular->id, 'nro_orden' => 'TIT-1']);
        $this->viaje(['chofer_id' => $suplente->id, 'nro_orden' => 'SUP-1']);

        $this->actingAs($this->usuario)
            ->get(route('viajes.index', ['chofer_id' => $suplente->id]))
            ->assertOk()
            ->assertSee('SUP-1')
            ->assertDontSee('TIT-1');
    }

    public function test_las_pantallas_de_alta_y_edicion_se_muestran(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira', 'dni' => '12.345.678']);

        $this->actingAs($this->usuario)->get(route('choferes.create'))
            ->assertOk()->assertSee('Nuevo Chofer');

        $this->actingAs($this->usuario)->get(route('choferes.edit', $chofer))
            ->assertOk()->assertSee('value="12.345.678"', false);
    }

    public function test_el_listado_de_viajes_muestra_el_chofer(): void
    {
        $chofer = Chofer::create(['nombre' => 'Rivadeneira']);
        $this->viaje(['chofer_id' => $chofer->id]);

        $this->actingAs($this->usuario)
            ->get(route('viajes.index'))
            ->assertOk()
            ->assertSee('Camión / Chofer')
            ->assertSee('Rivadeneira');
    }
}
