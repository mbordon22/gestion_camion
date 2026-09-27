<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
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
        $this->camion = Camion::create(['patente' => 'AB123CD', 'activo' => true]);
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

    public function test_se_puede_crear_un_cliente_y_el_cuit_queda_con_guiones(): void
    {
        $this->actingAs($this->usuario)->post(route('clientes.store'), [
            'nombre' => 'Control Union',
            'cuit'   => '30123456789',
            'activo' => '1',
        ])->assertRedirect(route('clientes.index'));

        $cliente = Cliente::first();
        $this->assertSame('Control Union', $cliente->nombre);
        $this->assertSame('30-12345678-9', $cliente->cuit);
        $this->assertTrue($cliente->activo);
    }

    public function test_no_se_repite_el_nombre_ni_se_acepta_un_cuit_mal_escrito(): void
    {
        Cliente::create(['nombre' => 'Control Union']);

        $this->actingAs($this->usuario)->post(route('clientes.store'), [
            'nombre' => 'Control Union',
            'cuit'   => '123',
        ])->assertSessionHasErrors(['nombre', 'cuit']);

        $this->assertSame(1, Cliente::count());
    }

    public function test_el_listado_muestra_cuanto_falta_cobrar_de_cada_cliente(): void
    {
        $cliente = Cliente::create(['nombre' => 'Control Union']);
        $this->viaje(['cliente_id' => $cliente->id, 'total' => 210000, 'cobrado' => false]);
        $this->viaje(['cliente_id' => $cliente->id, 'total' => 90000, 'cobrado' => true]);

        $this->actingAs($this->usuario)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Control Union')
            ->assertSee('$ 210.000,00')
            ->assertSee('Ver viajes');
    }

    public function test_un_cliente_con_viajes_se_desactiva_en_vez_de_borrarse(): void
    {
        $conViajes = Cliente::create(['nombre' => 'Control Union']);
        $sinViajes = Cliente::create(['nombre' => 'Ingenio La Florida']);
        $this->viaje(['cliente_id' => $conViajes->id]);

        $this->actingAs($this->usuario)->delete(route('clientes.destroy', $conViajes));
        $this->actingAs($this->usuario)->delete(route('clientes.destroy', $sinViajes));

        $this->assertFalse($conViajes->fresh()->activo);
        $this->assertNull($sinViajes->fresh());
    }

    public function test_el_formulario_de_viaje_sugiere_el_cliente_del_ultimo_viaje(): void
    {
        Cliente::create(['nombre' => 'Ingenio La Florida']);
        $ultimo = Cliente::create(['nombre' => 'Control Union']);
        $this->viaje(['cliente_id' => $ultimo->id]);

        $this->actingAs($this->usuario)
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('<option value="' . $ultimo->id . '" selected>Control Union</option>', false)
            ->assertSee('+ Nuevo cliente…');
    }

    public function test_se_puede_crear_el_cliente_desde_el_formulario_de_viaje(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje([
                'cliente_id'    => 'nuevo',
                'cliente_nuevo' => 'Ingenio La Florida',
            ]))
            ->assertRedirect(route('viajes.index'));

        $cliente = Cliente::firstWhere('nombre', 'Ingenio La Florida');
        $this->assertNotNull($cliente);
        $this->assertSame($cliente->id, Viaje::first()->cliente_id);
    }

    public function test_si_el_cliente_nuevo_ya_existia_se_usa_ese(): void
    {
        $existente = Cliente::create(['nombre' => 'Control Union']);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje([
            'cliente_id'    => 'nuevo',
            'cliente_nuevo' => 'Control Union',
        ]));

        $this->assertSame(1, Cliente::count());
        $this->assertSame($existente->id, Viaje::first()->cliente_id);
    }

    public function test_cliente_nuevo_sin_nombre_no_se_guarda(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje(['cliente_id' => 'nuevo']))
            ->assertSessionHasErrors('cliente_nuevo');

        $this->assertSame(0, Viaje::count());
    }

    public function test_la_orden_repetida_solo_cuenta_dentro_del_mismo_cliente(): void
    {
        $controlUnion = Cliente::create(['nombre' => 'Control Union']);
        $laFlorida    = Cliente::create(['nombre' => 'Ingenio La Florida']);
        $this->viaje(['cliente_id' => $controlUnion->id, 'nro_orden' => '10110']);

        $buscar = fn (array $params) => $this->actingAs($this->usuario)
            ->getJson(route('viajes.buscar-orden', $params + ['nro_orden' => '10110']));

        $buscar(['cliente_id' => $controlUnion->id])->assertJsonCount(1, 'viajes');
        $buscar(['cliente_id' => $laFlorida->id])->assertJsonCount(0, 'viajes');
        $buscar([])->assertJsonCount(1, 'viajes');
    }

    public function test_viajes_se_filtra_por_cliente(): void
    {
        $controlUnion = Cliente::create(['nombre' => 'Control Union']);
        $laFlorida    = Cliente::create(['nombre' => 'Ingenio La Florida']);
        $this->viaje(['cliente_id' => $controlUnion->id, 'nro_orden' => 'CU-1']);
        $this->viaje(['cliente_id' => $laFlorida->id, 'nro_orden' => 'LF-1']);

        $this->actingAs($this->usuario)
            ->get(route('viajes.index', ['cliente_id' => $laFlorida->id]))
            ->assertOk()
            ->assertSee('LF-1')
            ->assertDontSee('CU-1');
    }

    public function test_reportes_resume_ingresos_por_cliente(): void
    {
        $controlUnion = Cliente::create(['nombre' => 'Control Union']);
        $this->viaje(['cliente_id' => $controlUnion->id, 'total' => 210000, 'cobrado' => true]);
        $this->viaje(['cliente_id' => $controlUnion->id, 'total' => 210000, 'cobrado' => false]);
        $this->viaje(['total' => 50000]);

        $this->actingAs($this->usuario)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Por cliente', 'Control Union', '2', '$ 420.000,00', '$ 210.000,00', '$ 210.000,00'])
            ->assertSeeInOrder(['Sin cliente', '1', '$ 50.000,00']);
    }
}
