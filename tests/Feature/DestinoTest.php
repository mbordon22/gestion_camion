<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DestinoTest extends TestCase
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

    public function test_se_puede_crear_un_destino_con_su_distancia(): void
    {
        $cliente = Cliente::create(['nombre' => 'EFASS Servicios']);

        $this->actingAs($this->usuario)->post(route('destinos.store'), [
            'nombre'     => 'Churqui',
            'cliente_id' => $cliente->id,
            'origen'     => 'Ingenio La Corona',
            'km'         => 12,
            'activo'     => '1',
        ])->assertRedirect(route('destinos.index'));

        $destino = Destino::first();
        $this->assertSame('Churqui', $destino->nombre);
        $this->assertSame(12, $destino->km);
        $this->assertSame('Ingenio La Corona', $destino->origen);
        $this->assertSame('Churqui (EFASS Servicios)', $destino->etiqueta());
    }

    public function test_el_nombre_no_se_repite_dentro_del_mismo_cliente_pero_si_entre_clientes(): void
    {
        $efass   = Cliente::create(['nombre' => 'EFASS Servicios']);
        $control = Cliente::create(['nombre' => 'Control Union']);
        Destino::create(['nombre' => 'Churqui', 'cliente_id' => $efass->id, 'km' => 12]);

        // Mismo cliente: no.
        $this->actingAs($this->usuario)->post(route('destinos.store'), [
            'nombre'     => 'Churqui',
            'cliente_id' => $efass->id,
        ])->assertSessionHasErrors('nombre');

        // Otro cliente, a otra distancia: sí.
        $this->actingAs($this->usuario)->post(route('destinos.store'), [
            'nombre'     => 'Churqui',
            'cliente_id' => $control->id,
            'km'         => 30,
        ])->assertRedirect(route('destinos.index'));

        $this->assertSame(2, Destino::count());
    }

    public function test_el_listado_avisa_de_los_destinos_sin_distancia(): void
    {
        Destino::create(['nombre' => 'Churqui', 'km' => 12]);
        Destino::create(['nombre' => 'La Ramada']);

        $this->actingAs($this->usuario)
            ->get(route('destinos.index'))
            ->assertOk()
            ->assertSee('Hay 1 destino sin distancia cargada.')
            ->assertSee('La Ramada')
            ->assertSee('12 km');
    }

    public function test_un_destino_con_viajes_se_desactiva_en_vez_de_borrarse(): void
    {
        $conViajes = Destino::create(['nombre' => 'Churqui', 'km' => 12]);
        $sinViajes = Destino::create(['nombre' => 'La Ramada', 'km' => 30]);
        $this->viaje(['destino' => 'Churqui']);

        $this->actingAs($this->usuario)->delete(route('destinos.destroy', $conViajes));
        $this->actingAs($this->usuario)->delete(route('destinos.destroy', $sinViajes));

        $this->assertFalse($conViajes->fresh()->activo);
        $this->assertNull($sinViajes->fresh());
    }

    public function test_las_pantallas_de_alta_y_edicion_se_muestran(): void
    {
        Cliente::create(['nombre' => 'EFASS Servicios']);
        $destino = Destino::create(['nombre' => 'Churqui', 'km' => 12]);

        $this->actingAs($this->usuario)->get(route('destinos.create'))
            ->assertOk()->assertSee('Nuevo Destino')->assertSee('EFASS Servicios');

        $this->actingAs($this->usuario)->get(route('destinos.edit', $destino))
            ->assertOk()->assertSee('value="12"', false);
    }

    public function test_el_formulario_de_viaje_ofrece_los_destinos_con_sus_km(): void
    {
        Destino::create(['nombre' => 'Churqui', 'km' => 12, 'origen' => 'Ingenio La Corona']);

        $this->actingAs($this->usuario)
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('data-km="12"', false)
            ->assertSee('data-origen="Ingenio La Corona"', false)
            ->assertSee('+ Nuevo destino…');
    }

    public function test_el_destino_nuevo_queda_en_el_catalogo_con_los_km_del_viaje(): void
    {
        $cliente = Cliente::create(['nombre' => 'EFASS Servicios']);

        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje([
                'cliente_id'    => $cliente->id,
                'destino'       => '__nuevo__',
                'destino_nuevo' => '  Churqui  ',
                'origen'        => 'Ingenio La Corona',
                'km_recorridos' => 12,
            ]))
            ->assertRedirect(route('viajes.index'));

        // El viaje guarda el nombre, no el centinela ni los espacios.
        $this->assertSame('Churqui', Viaje::first()->destino);

        $destino = Destino::first();
        $this->assertSame('Churqui', $destino->nombre);
        $this->assertSame(12, $destino->km);
        $this->assertSame('Ingenio La Corona', $destino->origen);
        $this->assertSame($cliente->id, $destino->cliente_id);
    }

    public function test_el_destino_nuevo_sin_nombre_no_se_guarda(): void
    {
        $this->actingAs($this->usuario)
            ->post(route('viajes.store'), $this->datosViaje(['destino' => '__nuevo__']))
            ->assertSessionHasErrors('destino_nuevo');

        $this->assertSame(0, Viaje::count());
        $this->assertSame(0, Destino::count());
    }

    public function test_un_destino_ya_existente_no_se_duplica(): void
    {
        $cliente = Cliente::create(['nombre' => 'EFASS Servicios']);
        Destino::create(['nombre' => 'Churqui', 'cliente_id' => $cliente->id, 'km' => 12]);

        $this->actingAs($this->usuario)->post(route('viajes.store'), $this->datosViaje([
            'cliente_id'    => $cliente->id,
            'destino'       => '__nuevo__',
            'destino_nuevo' => 'Churqui',
            'km_recorridos' => 99,
        ]))->assertRedirect(route('viajes.index'));

        $this->assertSame(1, Destino::count());
        // Los km del catálogo no se pisan desde el viaje.
        $this->assertSame(12, Destino::first()->km);
    }

    public function test_el_viaje_conserva_un_destino_que_no_esta_en_el_catalogo(): void
    {
        $viaje = $this->viaje(['destino' => 'Un lugar viejo']);

        $this->actingAs($this->usuario)
            ->get(route('viajes.edit', $viaje))
            ->assertOk()
            ->assertSee('<option value="Un lugar viejo" selected>Un lugar viejo</option>', false);
    }

    public function test_la_migracion_junta_las_variantes_de_mayusculas(): void
    {
        $cliente = Cliente::create(['nombre' => 'Control Union']);

        // Así están los datos reales: la misma parada escrita de tres formas.
        foreach (['CONTROL UNION - SCANIA', 'CONTROL UNION - SCANIA', 'Control Union - Scania'] as $destino) {
            $this->viaje(['destino' => $destino, 'cliente_id' => $cliente->id]);
        }
        $this->viaje(['destino' => 'DEP. CONTROL UNION - SCANIA', 'cliente_id' => $cliente->id]);

        $migracion = require database_path('migrations/2026_09_21_000004_create_destinos_table.php');
        $migracion->down();
        $migracion->up();

        $destinos = DB::table('destinos')->pluck('nombre');

        $this->assertCount(2, $destinos);
        // Queda la grafía más usada, y el cliente porque todos los viajes son suyos.
        $this->assertTrue($destinos->contains('CONTROL UNION - SCANIA'));
        $this->assertTrue($destinos->contains('DEP. CONTROL UNION - SCANIA'));
        $this->assertSame($cliente->id, (int) DB::table('destinos')->where('nombre', 'CONTROL UNION - SCANIA')->value('cliente_id'));
    }
}
