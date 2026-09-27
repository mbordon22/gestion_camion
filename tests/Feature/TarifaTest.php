<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cliente;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Cliente $efass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create();

        // La migración de camiones inserta un "CAMION-1" por defecto.
        Camion::query()->delete();
        Camion::create(['patente' => 'TWH728', 'activo' => true]);

        $this->efass = Cliente::create(['nombre' => 'EFASS Servicios']);
    }

    /** Los tres rangos reales de la vinaza. */
    private function tarifasDeVinaza(string $desde = '2026-09-01', array $importes = [6000, 8500, 11000]): void
    {
        foreach ([[0, 8], [9, 15], [16, 35]] as $i => [$kmDesde, $kmHasta]) {
            $importe = $importes[$i];

            Tarifa::create([
                'cliente_id'    => $this->efass->id,
                'producto'      => 'Vinaza',
                'km_desde'      => $kmDesde,
                'km_hasta'      => $kmHasta,
                'importe'       => $importe,
                'unidad'        => 'toneladas',
                'vigente_desde' => $desde,
            ]);
        }
    }

    private function sugerir(array $params)
    {
        return $this->actingAs($this->usuario)->getJson(route('tarifas.sugerir', $params));
    }

    public function test_se_puede_cargar_una_tarifa(): void
    {
        $this->actingAs($this->usuario)->post(route('tarifas.store'), [
            'cliente_id'    => $this->efass->id,
            'producto'      => 'Vinaza',
            'km_desde'      => 9,
            'km_hasta'      => 15,
            'importe'       => 8500,
            'unidad'        => 'toneladas',
            'vigente_desde' => '2026-09-01',
        ])->assertRedirect(route('tarifas.index'));

        $tarifa = Tarifa::first();
        $this->assertSame('9 a 15 km', $tarifa->rango());
        $this->assertSame('$ 8.500,00 por tonelada', $tarifa->precioPorUnidad());
    }

    public function test_el_rango_no_puede_terminar_antes_de_empezar(): void
    {
        $this->actingAs($this->usuario)->post(route('tarifas.store'), [
            'cliente_id'    => $this->efass->id,
            'km_desde'      => 15,
            'km_hasta'      => 9,
            'importe'       => 8500,
            'unidad'        => 'toneladas',
            'vigente_desde' => '2026-09-01',
        ])->assertSessionHasErrors('km_hasta');

        $this->assertSame(0, Tarifa::count());
    }

    public function test_dos_tarifas_de_la_misma_vigencia_no_pueden_pisarse(): void
    {
        $this->tarifasDeVinaza();

        // 5 a 20 se superpone con los tres rangos cargados.
        $this->actingAs($this->usuario)->post(route('tarifas.store'), [
            'cliente_id'    => $this->efass->id,
            'producto'      => 'Vinaza',
            'km_desde'      => 5,
            'km_hasta'      => 20,
            'importe'       => 9000,
            'unidad'        => 'toneladas',
            'vigente_desde' => '2026-09-01',
        ])->assertSessionHasErrors('km_desde');

        $this->assertSame(3, Tarifa::count());
    }

    public function test_el_mismo_rango_con_otra_vigencia_si_se_puede_cargar(): void
    {
        $this->tarifasDeVinaza();

        $this->actingAs($this->usuario)->post(route('tarifas.store'), [
            'cliente_id'    => $this->efass->id,
            'producto'      => 'Vinaza',
            'km_desde'      => 9,
            'km_hasta'      => 15,
            'importe'       => 11000,
            'unidad'        => 'toneladas',
            'vigente_desde' => '2026-10-01',
        ])->assertRedirect(route('tarifas.index'));

        $this->assertSame(4, Tarifa::count());
    }

    public function test_cada_rango_de_km_devuelve_su_importe(): void
    {
        $this->tarifasDeVinaza();

        $base = ['cliente_id' => $this->efass->id, 'producto' => 'Vinaza', 'fecha' => '2026-09-20'];

        $this->sugerir($base + ['km' => 5])->assertJsonPath('tarifa.importe', 6000);
        $this->sugerir($base + ['km' => 12])->assertJsonPath('tarifa.importe', 8500);
        $this->sugerir($base + ['km' => 30])->assertJsonPath('tarifa.importe', 11000);

        // Los bordes de cada rango entran.
        $this->sugerir($base + ['km' => 8])->assertJsonPath('tarifa.importe', 6000);
        $this->sugerir($base + ['km' => 9])->assertJsonPath('tarifa.importe', 8500);

        // Fuera de todo rango no hay nada que proponer.
        $this->sugerir($base + ['km' => 80])->assertJsonPath('tarifa', null);
    }

    public function test_el_detalle_explica_de_donde_sale_el_precio(): void
    {
        $this->tarifasDeVinaza();

        $this->sugerir([
            'cliente_id' => $this->efass->id,
            'producto'   => 'Vinaza',
            'km'         => 12,
            'fecha'      => '2026-09-20',
        ])->assertJsonPath('tarifa.detalle', '9 a 15 km · $ 8.500,00 por tonelada · desde el 01/09/2026')
          ->assertJsonPath('tarifa.unidad', 'toneladas');
    }

    public function test_un_viaje_viejo_resuelve_con_la_tarifa_que_regia_ese_dia(): void
    {
        $this->tarifasDeVinaza('2026-09-01');
        // En octubre les actualizaron el precio.
        $this->tarifasDeVinaza('2026-10-01', [9000, 12000, 15000]);

        $base = ['cliente_id' => $this->efass->id, 'producto' => 'Vinaza', 'km' => 12];

        $this->sugerir($base + ['fecha' => '2026-09-20'])->assertJsonPath('tarifa.importe', 8500);
        $this->sugerir($base + ['fecha' => '2026-10-15'])->assertJsonPath('tarifa.importe', 12000);
    }

    public function test_la_fecha_del_formulario_trae_hora_y_igual_resuelve(): void
    {
        // El campo del viaje es datetime-local: manda "2026-09-20T18:47".
        $this->tarifasDeVinaza('2026-09-20');

        $base = ['cliente_id' => $this->efass->id, 'producto' => 'Vinaza', 'km' => 12];

        $this->sugerir($base + ['fecha' => '2026-09-20T18:47'])->assertJsonPath('tarifa.importe', 8500);
        $this->sugerir($base + ['fecha' => '2026-09-19T18:47'])->assertJsonPath('tarifa', null);
    }

    public function test_la_tarifa_del_producto_le_gana_a_la_general(): void
    {
        Tarifa::create([
            'cliente_id' => $this->efass->id, 'producto' => null,
            'km_desde' => 0, 'km_hasta' => 100, 'importe' => 5000,
            'unidad' => 'toneladas', 'vigente_desde' => '2026-09-01',
        ]);
        Tarifa::create([
            'cliente_id' => $this->efass->id, 'producto' => 'Vinaza',
            'km_desde' => 0, 'km_hasta' => 100, 'importe' => 8500,
            'unidad' => 'toneladas', 'vigente_desde' => '2026-09-01',
        ]);

        $base = ['cliente_id' => $this->efass->id, 'km' => 12, 'fecha' => '2026-09-20'];

        $this->sugerir($base + ['producto' => 'Vinaza'])->assertJsonPath('tarifa.importe', 8500);
        $this->sugerir($base + ['producto' => 'Azúcar'])->assertJsonPath('tarifa.importe', 5000);
        $this->sugerir($base)->assertJsonPath('tarifa.importe', 5000);
    }

    public function test_la_tarifa_de_un_cliente_no_se_le_aplica_a_otro(): void
    {
        $this->tarifasDeVinaza();
        $otro = Cliente::create(['nombre' => 'Control Union']);

        $this->sugerir([
            'cliente_id' => $otro->id,
            'producto'   => 'Vinaza',
            'km'         => 12,
            'fecha'      => '2026-09-20',
        ])->assertJsonPath('tarifa', null);
    }

    public function test_sin_cliente_o_sin_km_no_se_propone_nada(): void
    {
        $this->tarifasDeVinaza();

        $this->sugerir(['producto' => 'Vinaza', 'km' => 12, 'fecha' => '2026-09-20'])
            ->assertJsonPath('tarifa', null);

        $this->sugerir(['cliente_id' => $this->efass->id, 'producto' => 'Vinaza', 'fecha' => '2026-09-20'])
            ->assertJsonPath('tarifa', null);
    }

    public function test_el_listado_marca_cual_rige_hoy(): void
    {
        $vieja = Tarifa::create([
            'cliente_id' => $this->efass->id, 'producto' => 'Vinaza',
            'km_desde' => 9, 'km_hasta' => 15, 'importe' => 8500,
            'unidad' => 'toneladas', 'vigente_desde' => today()->subMonth(),
        ]);
        $nueva = Tarifa::create([
            'cliente_id' => $this->efass->id, 'producto' => 'Vinaza',
            'km_desde' => 9, 'km_hasta' => 15, 'importe' => 12000,
            'unidad' => 'toneladas', 'vigente_desde' => today(),
        ]);
        $futura = Tarifa::create([
            'cliente_id' => $this->efass->id, 'producto' => 'Vinaza',
            'km_desde' => 9, 'km_hasta' => 15, 'importe' => 15000,
            'unidad' => 'toneladas', 'vigente_desde' => today()->addMonth(),
        ]);

        $this->assertFalse($vieja->estaVigente());
        $this->assertTrue($nueva->estaVigente());
        $this->assertFalse($futura->estaVigente());

        $this->actingAs($this->usuario)
            ->get(route('tarifas.index'))
            ->assertOk()
            ->assertSee('EFASS Servicios')
            ->assertSee('Vigente')
            ->assertSee('$ 12.000,00 por tonelada');
    }

    public function test_el_formulario_de_viaje_trae_el_aviso_de_tarifa(): void
    {
        $this->actingAs($this->usuario)
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('id="aviso-tarifa"', false)
            ->assertSee(route('tarifas.sugerir'), false);
    }

    public function test_las_pantallas_de_alta_y_edicion_se_muestran(): void
    {
        $this->tarifasDeVinaza();

        $this->actingAs($this->usuario)->get(route('tarifas.create'))
            ->assertOk()->assertSee('Nueva Tarifa')->assertSee('EFASS Servicios');

        $this->actingAs($this->usuario)->get(route('tarifas.edit', Tarifa::first()))
            ->assertOk()->assertSee('Editar Tarifa');
    }
}
