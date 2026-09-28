<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Viaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductoTest extends TestCase
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
            'fecha'      => '2026-09-20',
            'total'      => 100000,
        ]);
    }

    private function tarifa(array $datos = []): Tarifa
    {
        return Tarifa::create($datos + [
            'cliente_id'    => Cliente::create(['nombre' => 'Control Union'])->id,
            'km_desde'      => 16,
            'km_hasta'      => 35,
            'importe'       => 9094.25,
            'unidad'        => 'toneladas',
            'vigente_desde' => '2026-09-19',
        ]);
    }

    public function test_se_puede_crear_un_producto_con_su_unidad(): void
    {
        $this->actingAs($this->usuario)->post(route('productos.store'), [
            'nombre' => '  Cereal ',
            'unidad' => 'toneladas',
            'activo' => '1',
        ])->assertRedirect(route('productos.index'));

        $producto = Producto::first();
        $this->assertSame('Cereal', $producto->nombre);
        $this->assertSame('toneladas', $producto->unidad);
        $this->assertTrue($producto->activo);

        $this->actingAs($this->usuario)->get(route('productos.index'))
            ->assertOk()
            ->assertSee('Cereal')
            ->assertSee('Toneladas');
    }

    public function test_no_se_repite_el_nombre(): void
    {
        Producto::create(['nombre' => 'Cereal']);

        $this->actingAs($this->usuario)->post(route('productos.store'), ['nombre' => 'Cereal'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Producto::count());
    }

    public function test_renombrarlo_corrige_los_viajes_y_las_tarifas(): void
    {
        $producto = Producto::create(['nombre' => 'Azucar', 'unidad' => 'bolsas']);
        $this->viaje(['producto' => 'Azucar']);
        $this->viaje(['producto' => 'Azucar']);
        $otro = $this->viaje(['producto' => 'Vinaza']);
        $tarifa = $this->tarifa(['producto' => 'Azucar']);

        $this->actingAs($this->usuario)->put(route('productos.update', $producto), [
            'nombre' => 'Azúcar',
            'unidad' => 'bolsas',
            'activo' => '1',
        ])->assertRedirect(route('productos.index'))
            ->assertSessionHas('success', 'Producto actualizado correctamente. También se corrigió en 2 viajes.');

        $this->assertSame(2, Viaje::where('producto', 'Azúcar')->count());
        $this->assertSame('Vinaza', $otro->fresh()->producto);
        // Sin esto, la tarifa dejaría de encontrarse.
        $this->assertSame('Azúcar', $tarifa->fresh()->producto);
    }

    public function test_si_ya_se_uso_se_desactiva_en_vez_de_borrarse(): void
    {
        $usado = Producto::create(['nombre' => 'Vinaza']);
        $sinUsar = Producto::create(['nombre' => 'Hacienda']);
        $this->viaje(['producto' => 'Vinaza']);

        $this->actingAs($this->usuario)->delete(route('productos.destroy', $usado));
        $this->actingAs($this->usuario)->delete(route('productos.destroy', $sinUsar));

        $this->assertFalse($usado->fresh()->activo);
        $this->assertNull($sinUsar->fresh());
    }

    public function test_el_viaje_elige_el_producto_del_catalogo_con_su_unidad(): void
    {
        Producto::create(['nombre' => 'Vinaza', 'unidad' => 'toneladas']);
        Producto::create(['nombre' => 'Viejo', 'activo' => false]);

        $this->actingAs($this->usuario)->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('<option value="Vinaza" data-unidad="toneladas"', false)
            ->assertSee('+ Nuevo producto…')
            // Los inactivos no se ofrecen.
            ->assertDontSee('value="Viejo"', false);
    }

    public function test_un_viaje_viejo_conserva_un_producto_inactivo(): void
    {
        Producto::create(['nombre' => 'Viejo', 'activo' => false]);
        $viaje = $this->viaje(['producto' => 'Viejo']);

        $this->actingAs($this->usuario)->get(route('viajes.edit', $viaje))
            ->assertOk()
            ->assertSee('value="Viejo"', false);
    }

    public function test_un_producto_nuevo_desde_el_viaje_queda_en_el_catalogo(): void
    {
        $this->actingAs($this->usuario)->post(route('viajes.store'), [
            'camion_id'       => $this->camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-09-20',
            'producto'        => '__nuevo__',
            'producto_nuevo'  => 'Cereal',
            'cantidad'        => '30',
            'unidad'          => 'toneladas',
            'precio_unitario' => '5.000',
        ])->assertRedirect(route('viajes.index'));

        $this->assertSame('Cereal', Viaje::first()->producto);
        $this->assertSame('toneladas', Producto::where('nombre', 'Cereal')->value('unidad'));
    }

    public function test_el_producto_nuevo_exige_el_nombre(): void
    {
        $this->actingAs($this->usuario)->post(route('viajes.store'), [
            'camion_id'  => $this->camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-09-20',
            'total'      => 1000,
            'producto'   => '__nuevo__',
        ])->assertSessionHasErrors('producto_nuevo');

        $this->assertSame(0, Viaje::count());
    }

    public function test_la_tarifa_elige_el_producto_del_catalogo(): void
    {
        Producto::create(['nombre' => 'Vinaza']);

        $this->actingAs($this->usuario)->get(route('tarifas.create'))
            ->assertOk()
            ->assertSee('<option value="">Cualquier carga</option>', false)
            ->assertSee('<option value="Vinaza" >Vinaza</option>', false);
    }

    public function test_la_migracion_arma_el_catalogo_con_lo_ya_cargado(): void
    {
        // La misma carga escrita de dos formas, y casi siempre en bolsas.
        $this->viaje(['producto' => 'Azúcar', 'cantidad' => 800, 'unidad' => 'bolsas']);
        $this->viaje(['producto' => 'Azúcar', 'cantidad' => 700, 'unidad' => 'bolsas']);
        $this->viaje(['producto' => 'azúcar', 'cantidad' => 20, 'unidad' => 'toneladas']);
        $this->viaje(['producto' => 'Vinaza']);
        // Un producto que sólo figura en una tarifa.
        $this->tarifa(['producto' => 'Cereal']);

        $migracion = require database_path('migrations/2026_09_28_000001_create_productos_table.php');
        $migracion->down();
        $migracion->up();

        $productos = DB::table('productos')->orderBy('nombre')->pluck('unidad', 'nombre');

        $this->assertSame(['Azúcar' => 'bolsas', 'Cereal' => null, 'Vinaza' => null], $productos->all());
    }
}
