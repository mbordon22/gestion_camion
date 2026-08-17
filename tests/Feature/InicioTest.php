<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Combustible;
use App\Models\Viaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inicio_muestra_el_resultado_del_mes(): void
    {
        $camion = Camion::create(['patente' => 'AB123CD', 'activo' => true]);

        Viaje::create([
            'camion_id'  => $camion->id,
            'modo_cobro' => 'fijo',
            'fecha'      => now(),
            'total'      => 500000,
            'cobrado'    => false,
        ]);

        Combustible::create([
            'camion_id'    => $camion->id,
            'fecha'        => now()->toDateString(),
            'litros'       => 300,
            'precio_litro' => 1000,
            'total'        => 300000,
        ]);

        $response = $this->actingAs(User::factory()->create())->get(route('inicio'));

        $response->assertOk()
            ->assertSee('Ganancia del mes')
            ->assertSee('200.000,00')   // 500.000 de viajes − 300.000 de combustible
            ->assertSee('Cargar viaje');

        // Lo que todavía no cobró tiene que aparecer aparte.
        $response->assertSee('Falta cobrar');
    }

    public function test_la_raiz_y_dashboard_llevan_al_inicio(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get('/')->assertRedirect(route('inicio'));
        $this->actingAs($usuario)->get('/dashboard')->assertRedirect(route('inicio'));
    }

    public function test_el_inicio_pide_login(): void
    {
        $this->get(route('inicio'))->assertRedirect(route('login'));
    }
}
