<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Cliente;
use App\Models\Combustible;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\User;
use App\Models\Viaje;
use App\Support\SimulacionViaje;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladorTest extends TestCase
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
        $this->camion = Camion::create(['patente' => 'TWH728', 'activo' => true, 'consumo_cada_100km' => 35]);
    }

    private function cisterna(): Equipo
    {
        return Equipo::firstOrCreate(['nombre' => 'Cisterna'], ['alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 25, 'activo' => true]);
    }

    private function antonio(): Chofer
    {
        return Chofer::firstOrCreate(['nombre' => 'Antonio'], ['modalidad' => 'porcentaje', 'valor' => 15, 'activo' => true]);
    }

    /** Un viaje de vinaza a 50 km: 28 t a $ 9.000, gasoil a $ 1.500, cisterna al 25 % y chofer al 15 %. */
    private function vinaza(array $cambios = []): SimulacionViaje
    {
        return new SimulacionViaje(...$cambios + [
            'km'             => 50,
            'vuelveVacio'    => true,
            'modo'           => 'cantidad',
            'cantidad'       => 28,
            'unidad'         => 'toneladas',
            'precioUnitario' => 9000,
            'consumo'        => 35,
            'precioLitro'    => 1500,
            'equipo'         => $this->cisterna(),
            'chofer'         => $this->antonio(),
            'peajes'         => 5000,
        ]);
    }

    public function test_la_cuenta_de_un_viaje_con_vuelta_vacia(): void
    {
        $simulacion = $this->vinaza();

        $this->assertSame(100.0, $simulacion->kmTotales());
        $this->assertSame(35.0, $simulacion->litros());
        $this->assertSame(252000.0, $simulacion->ingreso());
        $this->assertSame(52500.0, $simulacion->combustible());
        $this->assertSame(63000.0, $simulacion->alquiler());
        $this->assertSame(37800.0, $simulacion->comision());
        $this->assertSame(158300.0, $simulacion->totalGastos());
        $this->assertSame(93700.0, $simulacion->ganancia());
        $this->assertSame(37.2, $simulacion->margen());
        $this->assertSame(937.0, $simulacion->gananciaPorKm());
        $this->assertSame('conviene', $simulacion->veredicto()['nivel']);
        $this->assertSame('28 toneladas × $ 9.000', $simulacion->detalleIngreso());
        $this->assertSame(
            ['Combustible', 'Alquiler del equipo', 'Chofer', 'Peajes'],
            array_column($simulacion->gastos(), 'concepto')
        );
    }

    public function test_si_no_vuelve_vacio_el_gasoil_es_sólo_de_ida(): void
    {
        $simulacion = $this->vinaza(['vuelveVacio' => false]);

        $this->assertSame(50.0, $simulacion->kmTotales());
        $this->assertSame(17.5, $simulacion->litros());
        $this->assertSame(26250.0, $simulacion->combustible());
    }

    public function test_el_precio_para_no_perder_y_para_ganar_el_veinte_por_ciento(): void
    {
        $simulacion = $this->vinaza();

        // Fijos: $ 52.500 de gasoil + $ 5.000 de peajes. Equipo y chofer se
        // llevan el 40 % de lo que se cobre: queda el 60 % para cubrirlos.
        $this->assertSame(3422.62, $simulacion->precioPara(0));   // 57.500 / 0,6 / 28 t
        $this->assertSame(5133.93, $simulacion->precioPara(20));  // 57.500 / 0,4 / 28 t

        // Cobrando justo eso, la ganancia da cero.
        $justo = $this->vinaza(['precioUnitario' => 3422.62]);
        $this->assertEqualsWithDelta(0, $justo->ganancia(), 0.1);
        $this->assertSame('tonelada', $justo->unidadSingular());
    }

    public function test_un_equipo_propio_y_un_chofer_sin_comision_no_cuestan(): void
    {
        $propio = Equipo::create(['nombre' => 'Batea', 'alquilado' => false, 'activo' => true]);
        $sinComision = Chofer::create(['nombre' => 'Yo', 'activo' => true]);

        $simulacion = $this->vinaza(['equipo' => $propio, 'chofer' => $sinComision]);

        $this->assertSame(0.0, $simulacion->alquiler());
        $this->assertSame(0.0, $simulacion->comision());
        $this->assertSame(252000 - 52500 - 5000.0, $simulacion->ganancia());
    }

    public function test_los_acuerdos_por_monto_fijo_se_restan_enteros(): void
    {
        $equipo = Equipo::create(['nombre' => 'Acoplado', 'alquilado' => true, 'modalidad' => 'fijo_viaje', 'valor' => 20000, 'activo' => true]);

        $simulacion = $this->vinaza(['equipo' => $equipo, 'chofer' => null, 'peajes' => 0]);

        $this->assertSame(20000.0, $simulacion->alquiler());
        // Fijos: 52.500 + 20.000, sin porcentajes: el precio mínimo es ése entre 28.
        $this->assertSame(2589.29, $simulacion->precioPara(0));
    }

    public function test_con_precio_cerrado_cotiza_el_total(): void
    {
        $simulacion = $this->vinaza(['modo' => 'fijo', 'totalFijo' => 100000, 'equipo' => null, 'chofer' => null]);

        $this->assertSame(100000.0, $simulacion->ingreso());
        $this->assertSame(57500.0, $simulacion->precioPara(0));
        $this->assertNull($simulacion->detalleIngreso());
    }

    public function test_avisa_cuando_se_pierde_plata(): void
    {
        $simulacion = $this->vinaza(['precioUnitario' => 2000]);

        $this->assertLessThan(0, $simulacion->ganancia());
        $this->assertSame('pierde', $simulacion->veredicto()['nivel']);
        $this->assertSame('Perdés plata', $simulacion->veredicto()['texto']);
    }

    public function test_si_los_porcentajes_se_llevan_todo_ningun_precio_alcanza(): void
    {
        $equipo = Equipo::create(['nombre' => 'Caro', 'alquilado' => true, 'modalidad' => 'porcentaje', 'valor' => 90, 'activo' => true]);

        $simulacion = $this->vinaza(['equipo' => $equipo]);

        $this->assertNull($simulacion->precioPara(0));
    }

    public function test_sin_lo_que_se_cobra_no_hay_veredicto(): void
    {
        $simulacion = new SimulacionViaje(km: 50, consumo: 35, precioLitro: 1500);

        $this->assertNull($simulacion->margen());
        $this->assertSame('sin_datos', $simulacion->veredicto()['nivel']);
    }

    public function test_la_pantalla_propone_el_consumo_el_litro_y_lo_del_ultimo_viaje(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ingenio la Corona', 'activo' => true]);
        $chofer = $this->antonio();
        Combustible::create(['camion_id' => $this->camion->id, 'fecha' => '2026-09-25', 'litros' => 200, 'precio_litro' => 1973.12, 'total' => 394624]);
        Viaje::create([
            'camion_id' => $this->camion->id, 'cliente_id' => $cliente->id, 'chofer_id' => $chofer->id,
            'modo_cobro' => 'cantidad', 'fecha' => '2026-09-26', 'producto' => 'Vinaza',
            'cantidad' => 28, 'unidad' => 'toneladas', 'precio_unitario' => 9000, 'total' => 252000,
        ]);

        $this->actingAs($this->usuario)->get(route('simulador.index'))
            ->assertOk()
            ->assertSee('Simular viaje')
            ->assertSee('value="35"', false)  // el consumo del camión
            ->assertSee('value="1.973,12"', false)
            ->assertSee('Tu última carga: $ 1.973,12 (25/09).')
            ->assertSee('<option value="' . $cliente->id . '" selected>Ingenio la Corona</option>', false)
            ->assertSee('Cargá lo que cobrás para ver si conviene');
    }

    public function test_el_resultado_se_calcula_con_los_datos_de_la_url(): void
    {
        $equipo = $this->cisterna();
        $chofer = $this->antonio();

        $this->actingAs($this->usuario)->get(route('simulador.resultado', [
            'km' => '50', 'vuelta' => '1', 'modo' => 'cantidad',
            'cantidad' => '28', 'unidad' => 'toneladas', 'precio_unitario' => '9.000',
            'consumo' => '35', 'precio_litro' => '1.500',
            'equipo_id' => $equipo->id, 'chofer_id' => $chofer->id, 'peajes' => '5.000',
        ]))
            ->assertOk()
            ->assertSee('Te conviene')
            ->assertSee('$ 93.700,00')
            ->assertSee('37,2 %')
            ->assertSee('100 km · 35 L a $ 1.500')
            ->assertSee('$ 3.422,62 por tonelada')
            ->assertSee('$ 5.133,93 por tonelada')
            // Sin el layout: es sólo el pedazo que se reemplaza.
            ->assertDontSee('<html', false);

        // A $ 1.000 la tonelada no alcanza ni para el gasoil (28.000 − 52.500).
        $this->actingAs($this->usuario)->get(route('simulador.resultado', [
            'km' => '50', 'vuelta' => '1', 'modo' => 'cantidad', 'cantidad' => '28', 'unidad' => 'toneladas',
            'precio_unitario' => '1.000', 'consumo' => '35', 'precio_litro' => '1.500',
        ]))
            ->assertSee('Perdés plata')
            ->assertSee('−$ 24.500,00')
            ->assertSee('−87,5 %');
    }

    public function test_cargar_como_viaje_lleva_lo_simulado_al_formulario(): void
    {
        $cliente = Cliente::create(['nombre' => 'Ingenio la Corona', 'activo' => true]);
        Destino::create(['cliente_id' => $cliente->id, 'nombre' => 'Churqui', 'origen' => 'Ingenio la Corona', 'km' => 30, 'activo' => true]);

        $parametros = [
            'km' => '30', 'vuelta' => '1', 'modo' => 'cantidad', 'cliente_id' => $cliente->id, 'destino' => 'Churqui',
            'producto' => 'Vinaza', 'cantidad' => '28', 'unidad' => 'toneladas', 'precio_unitario' => '9.094,25',
            'consumo' => '35', 'precio_litro' => '1.500',
        ];

        $enlace = route('viajes.create', [
            'simulacion' => 1, 'camion_id' => $this->camion->id, 'cliente_id' => $cliente->id,
            'modo_cobro' => 'cantidad', 'producto' => 'Vinaza', 'cantidad' => '28', 'unidad' => 'toneladas',
            'precio_unitario' => '9.094,25', 'destino' => 'Churqui', 'km_recorridos' => '30',
        ]);

        $this->actingAs($this->usuario)->get(route('simulador.resultado', $parametros))
            ->assertSee(e($enlace), false);

        $this->actingAs($this->usuario)->get($enlace)
            ->assertOk()
            ->assertSee('Viene del simulador')
            ->assertDontSee('Repetir Viaje')
            ->assertSee('<option value="' . $cliente->id . '" selected>Ingenio la Corona</option>', false)
            ->assertSee('value="28"', false)
            ->assertSee('value="9.094,25"', false)
            ->assertSee('value="Ingenio la Corona"', false)  // el origen, del destino del catálogo
            ->assertSee('value="30"', false);
    }

    public function test_el_consumo_del_camion_se_carga_con_coma(): void
    {
        $this->actingAs($this->usuario)->put(route('camiones.update', $this->camion), [
            'patente' => 'TWH728', 'activo' => '1', 'consumo_cada_100km' => '37,5',
        ])->assertRedirect(route('camiones.index'));

        $this->assertEquals(37.5, $this->camion->fresh()->consumo_cada_100km);
    }
}
