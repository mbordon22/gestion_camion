<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Producto;
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

    /** La forma de cobro que el formulario trae marcada. */
    private function modoPropuesto(string $html): ?string
    {
        preg_match('/name="modo_cobro" value="(\w+)"[^>]*\schecked/', $html, $coincidencia);

        return $coincidencia[1] ?? null;
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
            ->assertSee('Precio cerrado')
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
            ->assertSee('<input type="hidden" name="camion_id" id="camion_id" value="' . $camion->id . '">', false)
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
        $this->assertSame('Precio cerrado', $viaje->resumenCarga());
    }

    public function test_el_monto_fijo_igual_guarda_la_carga_del_ticket(): void
    {
        $camion = $this->camion();

        // Ticket de vinaza: el flete se cobró cerrado, pero el neto pesado
        // tiene que quedar registrado igual.
        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-09-20T18:47',
            'producto'   => 'Vinaza',
            'cantidad'   => 27.7,
            'unidad'     => 'toneladas',
            'total'      => 150000,
        ])->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();

        $this->assertEquals(150000, $viaje->total);
        $this->assertEquals(27.7, $viaje->cantidad);
        $this->assertSame('toneladas', $viaje->unidad);
        $this->assertNull($viaje->precio_unitario);
        $this->assertSame('Vinaza · 27,7 toneladas', $viaje->resumenCarga());
    }

    public function test_la_vinaza_se_cobra_por_tonelada(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'       => $camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-09-20T18:47',
            'nro_orden'       => '00137965',
            'producto'        => 'Vinaza',
            'cantidad'        => 27.7,
            'unidad'          => 'toneladas',
            'precio_unitario' => 8500,
            'origen'          => 'Ingenio La Corona',
            'destino'         => 'Churqui',
            'km_recorridos'   => 12,
        ])->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();

        $this->assertEquals(235450, $viaje->total);
        $this->assertSame('00137965', $viaje->nro_orden);
        $this->assertSame(12, $viaje->km_recorridos);
        $this->assertSame('Vinaza · 27,7 toneladas', $viaje->resumenCarga());
    }

    public function test_la_cantidad_sin_unidad_no_se_guarda_a_medias(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-09-20T18:47',
            'cantidad'   => 27.7,
            'total'      => 150000,
        ])->assertSessionHasErrors('unidad');

        $this->assertSame(0, Viaje::count());
    }

    public function test_el_formulario_ofrece_solo_los_productos_del_catalogo(): void
    {
        $this->camion();
        Producto::create(['nombre' => 'Cereal']);

        $this->actingAs($this->usuario())
            ->get(route('viajes.create'))
            ->assertOk()
            ->assertSee('<option value="Cereal" data-unidad=""', false)
            // Nada fijo de un rubro en particular.
            ->assertDontSee('value="Vinaza"', false);
    }

    public function test_los_montos_se_pueden_escribir_con_puntos_y_coma(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'       => $camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-09-20',
            'cantidad'        => '27,7',
            'unidad'          => 'toneladas',
            'precio_unitario' => '$ 8.500',
        ])->assertRedirect(route('viajes.index'));

        $viaje = Viaje::first();
        $this->assertEquals(27.7, $viaje->cantidad);
        $this->assertEquals(8500, $viaje->precio_unitario);
        $this->assertEquals(235450, $viaje->total);

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-09-20',
            'total'      => '1.250.000,50',
        ])->assertRedirect(route('viajes.index'));

        $this->assertEquals(1250000.50, Viaje::latest('id')->first()->total);
    }

    public function test_un_monto_que_no_se_entiende_se_rechaza(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => '2026-09-20',
            'total'      => 'ciento cincuenta',
        ])->assertSessionHasErrors('total');

        $this->assertSame(0, Viaje::count());
    }

    public function test_la_fecha_va_sin_hora_y_la_hora_es_opcional(): void
    {
        $camion = $this->camion();
        $datos = ['camion_id' => $camion->id, 'modo_cobro' => 'fijo', 'total' => 1000];

        $this->actingAs($this->usuario())->post(route('viajes.store'), $datos + ['fecha' => '2026-09-20'])
            ->assertRedirect(route('viajes.index'));
        $this->actingAs($this->usuario())->post(route('viajes.store'), $datos + ['fecha' => '2026-09-21', 'hora' => '18:47'])
            ->assertRedirect(route('viajes.index'));

        [$sinHora, $conHora] = Viaje::orderBy('id')->get();
        $this->assertSame('2026-09-20 00:00', $sinHora->fecha->format('Y-m-d H:i'));
        $this->assertSame('2026-09-21 18:47', $conHora->fecha->format('Y-m-d H:i'));

        // Al editarlo, la hora que tenía vuelve al formulario.
        $this->actingAs($this->usuario())->get(route('viajes.edit', $conHora))
            ->assertSee('value="2026-09-21"', false)
            ->assertSee('value="18:47"', false);
    }

    public function test_sin_viajes_el_formulario_propone_un_precio_cerrado(): void
    {
        $this->camion();

        $respuesta = $this->actingAs($this->usuario())->get(route('viajes.create'))->assertOk();

        $this->assertSame('fijo', $this->modoPropuesto($respuesta->getContent()));
        $respuesta->assertDontSee('¿Igual que el último viaje?');
    }

    public function test_a_cada_cliente_se_le_propone_cobrar_como_la_ultima_vez(): void
    {
        $camion = $this->camion();
        $porTonelada = Cliente::create(['nombre' => 'Control Union', 'activo' => true]);
        $cerrado = Cliente::create(['nombre' => 'Acopio Norte', 'activo' => true]);

        Viaje::create([
            'camion_id' => $camion->id, 'cliente_id' => $porTonelada->id, 'modo_cobro' => 'cantidad',
            'fecha' => '2026-09-19', 'producto' => 'Vinaza', 'cantidad' => 27.7, 'unidad' => 'toneladas',
            'precio_unitario' => 8500, 'total' => 235450,
        ]);
        $ultimo = Viaje::create([
            'camion_id' => $camion->id, 'cliente_id' => $cerrado->id, 'modo_cobro' => 'fijo',
            'fecha' => '2026-09-20', 'total' => 150000,
        ]);

        $respuesta = $this->actingAs($this->usuario())->get(route('viajes.create'))->assertOk();
        $html = $respuesta->getContent();

        // El último viaje fue para Acopio Norte, a precio cerrado: así arranca.
        $this->assertSame('fijo', $this->modoPropuesto($html));
        $respuesta->assertSee('¿Igual que el último viaje?')
            ->assertSee(route('viajes.create', ['repetir' => $ultimo->id]), false);

        // Y el formulario sabe cómo se le cobró a cada uno, para cuando se cambie.
        $cobro = json_decode(html_entity_decode(
            str($html)->after('data-cobro="')->before('"')->toString()
        ), true);

        $this->assertSame(['modo' => 'cantidad', 'unidad' => 'toneladas', 'producto' => 'Vinaza'], $cobro[$porTonelada->id]);
        $this->assertSame('fijo', $cobro[$cerrado->id]['modo']);

        // Si el último hubiera sido por tonelada, arrancaría por cantidad.
        $ultimo->delete();
        $html = $this->actingAs($this->usuario())->get(route('viajes.create'))->getContent();
        $this->assertSame('cantidad', $this->modoPropuesto($html));
    }

    public function test_despues_de_guardar_se_ofrece_cargar_otro_igual(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->followingRedirects()->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => today()->toDateString(),
            'total'      => 150000,
        ])->assertSee('Cargar otro igual')
            ->assertSee(route('viajes.create', ['repetir' => Viaje::first()->id]), false);
    }

    public function test_repetir_un_viaje_de_precio_cerrado_trae_el_precio(): void
    {
        $camion = $this->camion();
        $original = Viaje::create([
            'camion_id' => $camion->id, 'modo_cobro' => 'fijo', 'fecha' => '2026-09-20', 'total' => 150000,
        ]);

        $this->actingAs($this->usuario())->get(route('viajes.create', ['repetir' => $original->id]))
            ->assertOk()
            ->assertSee('value="150.000"', false);
    }

    public function test_lo_que_no_se_usa_queda_en_mas_datos(): void
    {
        $camion = $this->camion();

        // Sin choferes ni viajes con orden, los dos van dentro de "Más datos".
        $this->actingAs($this->usuario())->get(route('viajes.create'))
            ->assertSeeInOrder(['<summary', 'name="nro_orden"', 'name="chofer_id"'], false);

        Chofer::create(['nombre' => 'Rivadeneira', 'activo' => true]);
        Viaje::create([
            'camion_id' => $camion->id, 'modo_cobro' => 'fijo', 'fecha' => '2026-09-20',
            'total' => 150000, 'nro_orden' => '10110',
        ]);

        // Una vez que se usan, quedan a la vista.
        $this->actingAs($this->usuario())->get(route('viajes.create'))
            ->assertSeeInOrder(['name="nro_orden"', 'name="chofer_id"', '<summary'], false);
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

    public function test_el_numero_de_orden_se_guarda_y_se_ve_en_el_listado(): void
    {
        $camion = $this->camion();

        $this->actingAs($this->usuario())->post(route('viajes.store'), [
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now()->format('Y-m-d\TH:i'),
            'total'      => 150000,
            'nro_orden'  => ' OC-00123 ',
        ])->assertRedirect(route('viajes.index'));

        $this->assertSame('OC-00123', Viaje::first()->nro_orden);

        $this->actingAs($this->usuario())
            ->get(route('viajes.index'))
            ->assertSee('OC-00123');
    }

    public function test_avisa_si_el_numero_de_orden_ya_esta_cargado(): void
    {
        $camion = $this->camion();
        $usuario = $this->usuario();
        $existente = Viaje::create([
            'camion_id'       => $camion->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-09-05 16:29',
            'nro_orden'       => '10110',
            'cantidad'        => 700,
            'unidad'          => 'bolsas',
            'precio_unitario' => 300,
            'total'           => 210000,
            'origen'          => 'Ingenio Concepción',
            'destino'         => 'Scania',
        ]);

        $this->actingAs($usuario)
            ->getJson(route('viajes.buscar-orden', ['nro_orden' => '10110']))
            ->assertOk()
            ->assertJsonCount(1, 'viajes')
            ->assertJsonPath('viajes.0.detalle', '05/09/2026 · Ingenio Concepción → Scania · 700 bolsas · $ 210.000,00')
            ->assertJsonPath('viajes.0.url', route('viajes.edit', $existente));

        // Al editar ese mismo viaje no tiene que avisar de sí mismo.
        $this->actingAs($usuario)
            ->getJson(route('viajes.buscar-orden', ['nro_orden' => '10110', 'excluir' => $existente->id]))
            ->assertOk()
            ->assertJsonCount(0, 'viajes');

        $this->actingAs($usuario)
            ->getJson(route('viajes.buscar-orden', ['nro_orden' => '99999']))
            ->assertOk()
            ->assertJsonCount(0, 'viajes');
    }

    public function test_la_migracion_pasa_el_numero_de_orden_desde_observaciones(): void
    {
        $camion = $this->camion();
        $viaje = fn (?string $observaciones) => Viaje::create([
            'camion_id'     => $camion->id,
            'modo_cobro'    => 'fijo',
            'fecha'         => '2026-08-31 14:56',
            'total'         => 100000,
            'observaciones' => $observaciones,
        ]);

        $formatos = [
            'Orden de carga: 9409'   => '9409',
            'Numero de orden 10110'  => '10110',
            'Número de orden 10270'  => '10270',
            'Numero de orden: 10781' => '10781',
        ];
        $conOrden    = collect($formatos)->keys()->map($viaje);
        $sinOrden    = $viaje('Ingenio la florida');
        $conMasTexto = $viaje('Orden de carga: 9409, pagó tarde');

        $migracion = require database_path('migrations/2026_09_19_000001_add_nro_orden_to_viajes_table.php');
        $migracion->down();
        $migracion->up();

        foreach ($conOrden as $i => $v) {
            $v->refresh();
            $this->assertSame(array_values($formatos)[$i], $v->nro_orden);
            $this->assertNull($v->observaciones);
        }
        $this->assertNull($sinOrden->fresh()->nro_orden);
        $this->assertSame('Ingenio la florida', $sinOrden->fresh()->observaciones);
        $this->assertNull($conMasTexto->fresh()->nro_orden);
        $this->assertSame('Orden de carga: 9409, pagó tarde', $conMasTexto->fresh()->observaciones);
    }

    /** Un viaje de vinaza completo, para repetirlo. */
    private function viajeDeVinaza(Camion $camion): Viaje
    {
        return Viaje::create([
            'camion_id'       => $camion->id,
            'cliente_id'      => Cliente::create(['nombre' => 'EFASS Servicios'])->id,
            'chofer_id'       => Chofer::create(['nombre' => 'Rivadeneira'])->id,
            'modo_cobro'      => 'cantidad',
            'fecha'           => '2026-09-20 18:47',
            'nro_orden'       => '00137965',
            'producto'        => 'Vinaza',
            'cantidad'        => 27.7,
            'unidad'          => 'toneladas',
            'precio_unitario' => 8500,
            'total'           => 235450,
            'origen'          => 'Ingenio La Corona',
            'destino'         => 'Churqui',
            'km_recorridos'   => 12,
            'cobrado'         => true,
            'observaciones'   => 'Entró tarde a la balanza',
        ]);
    }

    public function test_repetir_trae_la_ruta_la_carga_y_el_precio(): void
    {
        $camion = $this->camion();
        $original = $this->viajeDeVinaza($camion);

        $repetido = $this->actingAs($this->usuario())
            ->get(route('viajes.create', ['repetir' => $original->id]))
            ->assertOk()
            ->viewData('viaje');

        $this->assertSame($original->camion_id, $repetido->camion_id);
        $this->assertSame($original->cliente_id, $repetido->cliente_id);
        $this->assertSame($original->chofer_id, $repetido->chofer_id);
        $this->assertSame('cantidad', $repetido->modo_cobro);
        $this->assertSame('Vinaza', $repetido->producto);
        $this->assertSame('toneladas', $repetido->unidad);
        $this->assertEquals(8500, $repetido->precio_unitario);
        $this->assertSame('Ingenio La Corona', $repetido->origen);
        $this->assertSame('Churqui', $repetido->destino);
        $this->assertSame(12, $repetido->km_recorridos);

        // Todavía no se guardó nada: el viaje viejo sigue siendo el único.
        $this->assertFalse($repetido->exists);
        $this->assertSame(1, Viaje::count());
    }

    public function test_repetir_no_arrastra_lo_que_trae_el_ticket(): void
    {
        $camion = $this->camion();
        $original = $this->viajeDeVinaza($camion);

        $repetido = $this->actingAs($this->usuario())
            ->get(route('viajes.create', ['repetir' => $original->id]))
            ->assertOk()
            ->viewData('viaje');

        $this->assertNull($repetido->nro_orden);
        $this->assertNull($repetido->cantidad);
        $this->assertNull($repetido->total);
        $this->assertNull($repetido->observaciones);
        $this->assertNull($repetido->fecha_carga);
        $this->assertFalse($repetido->cobrado);
        $this->assertTrue($repetido->fecha->isToday());
    }

    public function test_el_formulario_repetido_llega_completo_al_navegador(): void
    {
        $camion = $this->camion();
        Destino::create(['nombre' => 'Churqui', 'km' => 12, 'origen' => 'Ingenio La Corona']);
        $original = $this->viajeDeVinaza($camion);

        $this->actingAs($this->usuario())
            ->get(route('viajes.create', ['repetir' => $original->id]))
            ->assertOk()
            ->assertSee('Repetir Viaje')
            ->assertSee('Falta lo que trae el ticket')
            ->assertSee('selected>Rivadeneira</option>', false)
            ->assertSee('selected>Churqui</option>', false)
            ->assertSee('value="Vinaza"', false)
            ->assertSee('value="Ingenio La Corona"', false)
            // La pesada y el peso del viaje anterior no tienen que venir.
            ->assertDontSee('value="00137965"', false)
            ->assertDontSee('value="27.7"', false)
            ->assertDontSee('value="235450.00"', false);
    }

    public function test_repetir_un_viaje_que_no_existe_abre_el_formulario_normal(): void
    {
        $this->camion();

        $this->actingAs($this->usuario())
            ->get(route('viajes.create', ['repetir' => 9999]))
            ->assertOk()
            ->assertSee('Nuevo Viaje')
            ->assertDontSee('Repetir Viaje');
    }

    public function test_el_listado_ofrece_repetir_cada_viaje(): void
    {
        $camion = $this->camion();
        $original = $this->viajeDeVinaza($camion);

        $this->actingAs($this->usuario())
            ->get(route('viajes.index'))
            ->assertOk()
            ->assertSee(route('viajes.create', ['repetir' => $original->id]), false)
            ->assertSee('Repetir');
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
