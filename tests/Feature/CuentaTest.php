<?php

namespace Tests\Feature;

use App\Models\Camion;
use App\Models\Cliente;
use App\Models\Cuenta;
use App\Models\MedioPago;
use App\Models\User;
use App\Models\Viaje;
use App\Support\CuentaActual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuentaTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private Cuenta $otra;
    private User $otroUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Yo (en la cuenta de prueba) y otro transportista en su propia cuenta.
        $this->usuario = User::factory()->create();
        $this->otra = Cuenta::create(['nombre' => 'Transportes Pérez', 'activa' => true]);
        $this->otroUsuario = User::factory()->create(['cuenta_id' => $this->otra->id]);
    }

    /** Crea datos como si estuviera logueado alguien de esa cuenta. */
    private function enCuenta(Cuenta $cuenta, \Closure $crear): mixed
    {
        CuentaActual::porDefecto($cuenta->id);

        try {
            return $crear();
        } finally {
            CuentaActual::porDefecto($this->cuenta->id);
        }
    }

    private function viajeDeLaOtra(): Viaje
    {
        return $this->enCuenta($this->otra, function () {
            $camion = Camion::create(['patente' => 'AA111AA', 'activo' => true]);
            $cliente = Cliente::create(['nombre' => 'Cliente de Pérez', 'activo' => true]);

            return Viaje::create([
                'camion_id' => $camion->id, 'cliente_id' => $cliente->id, 'modo_cobro' => 'fijo',
                'fecha' => today(), 'total' => 999999, 'destino' => 'Destino secreto',
            ]);
        });
    }

    private function admin(): User
    {
        $this->usuario->es_admin = true;
        $this->usuario->save();

        return $this->usuario;
    }

    // --- Cada cuenta, lo suyo ------------------------------------------------

    public function test_cada_cuenta_ve_solo_sus_viajes(): void
    {
        $ajeno = $this->viajeDeLaOtra();
        $camion = Camion::create(['patente' => 'BB222BB', 'activo' => true]);
        Viaje::create(['camion_id' => $camion->id, 'modo_cobro' => 'fijo', 'fecha' => today(), 'total' => 1000, 'destino' => 'Mi destino']);

        $this->actingAs($this->usuario)->get(route('viajes.index'))
            ->assertOk()
            ->assertSee('Mi destino')
            ->assertDontSee('Destino secreto')
            ->assertDontSee('Cliente de Pérez');

        $this->actingAs($this->otroUsuario)->get(route('viajes.index'))
            ->assertSee('Destino secreto')
            ->assertDontSee('Mi destino');

        $this->assertSame($this->otra->id, $ajeno->cuenta_id);
    }

    public function test_un_viaje_de_otra_cuenta_no_se_puede_abrir_ni_borrar(): void
    {
        $ajeno = $this->viajeDeLaOtra();

        $this->actingAs($this->usuario)->get(route('viajes.edit', $ajeno->id))->assertNotFound();
        $this->actingAs($this->usuario)->delete(route('viajes.destroy', $ajeno->id))->assertNotFound();
        $this->actingAs($this->usuario)->patch(route('viajes.cobrado', $ajeno->id))->assertNotFound();

        $this->assertNotNull(Viaje::withoutGlobalScope('cuenta')->find($ajeno->id));
    }

    public function test_no_se_puede_cargar_un_viaje_con_el_camion_de_otra_cuenta(): void
    {
        $ajeno = $this->viajeDeLaOtra();

        $this->actingAs($this->usuario)->post(route('viajes.store'), [
            'camion_id'  => $ajeno->camion_id,
            'cliente_id' => $ajeno->cliente_id,
            'modo_cobro' => 'fijo',
            'fecha'      => today()->toDateString(),
            'total'      => 1000,
        ])->assertSessionHasErrors(['camion_id', 'cliente_id']);
    }

    public function test_lo_que_se_carga_queda_en_la_cuenta_de_quien_lo_carga(): void
    {
        $this->actingAs($this->otroUsuario)->post(route('camiones.store'), [
            'patente' => 'CC333CC', 'activo' => '1',
        ])->assertRedirect(route('camiones.index'));

        $this->assertSame($this->otra->id, Camion::withoutGlobalScope('cuenta')->where('patente', 'CC333CC')->value('cuenta_id'));
    }

    public function test_los_nombres_se_pueden_repetir_entre_cuentas_pero_no_dentro_de_una(): void
    {
        $this->enCuenta($this->otra, fn () => Cliente::create(['nombre' => 'Control Union', 'activo' => true]));

        // Otro transportista ya tiene un "Control Union": yo también puedo.
        $this->actingAs($this->usuario)->post(route('clientes.store'), ['nombre' => 'Control Union', 'activo' => '1'])
            ->assertSessionHasNoErrors();

        // Pero no dos veces en mi cuenta.
        $this->actingAs($this->usuario)->post(route('clientes.store'), ['nombre' => 'Control Union', 'activo' => '1'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(2, Cliente::withoutGlobalScope('cuenta')->where('nombre', 'Control Union')->count());
    }

    // --- El panel del administrador --------------------------------------------

    public function test_el_panel_es_solo_del_administrador(): void
    {
        $this->actingAs($this->usuario)->get(route('admin.cuentas.index'))->assertForbidden();
        $this->actingAs($this->usuario)->get(route('inicio'))->assertDontSee(route('admin.cuentas.index'), false);

        $this->actingAs($this->admin())->get(route('admin.cuentas.index'))
            ->assertOk()
            ->assertSee('Transportes Pérez')
            ->assertSee('Cuenta de prueba')
            ->assertDontSee('.00<', false);  // la cantidad de viajes no es un monto
    }

    public function test_el_admin_crea_una_cuenta_con_su_usuario_y_lo_necesario_para_arrancar(): void
    {
        $respuesta = $this->actingAs($this->admin())->post(route('admin.cuentas.store'), [
            'nombre'   => 'Fletes Gómez',
            'patente'  => 'ad456zz',
            'usuario'  => 'Ana Gómez',
            'email'    => 'ana@gomez.com',
            'password' => 'camion2026',
        ]);

        $cuenta = Cuenta::where('nombre', 'Fletes Gómez')->firstOrFail();
        $respuesta->assertRedirect(route('admin.cuentas.edit', $cuenta));

        $ana = User::where('email', 'ana@gomez.com')->firstOrFail();
        $this->assertSame($cuenta->id, $ana->cuenta_id);
        $this->assertFalse($ana->esAdmin());

        // Lo que necesita para arrancar, en SU cuenta (no en la del admin).
        $this->assertSame(['Efectivo', 'Transferencia'],
            MedioPago::withoutGlobalScope('cuenta')->where('cuenta_id', $cuenta->id)->orderBy('nombre')->pluck('nombre')->all());
        $this->assertSame('AD456ZZ', Camion::withoutGlobalScope('cuenta')->where('cuenta_id', $cuenta->id)->value('patente'));

        // Los datos para pasarle, una vez.
        $this->actingAs($this->usuario)->get(route('admin.cuentas.edit', $cuenta))
            ->assertSee('Pasale estos datos para entrar')
            ->assertSee('camion2026');

        // Y entra, a su cuenta vacía.
        auth()->logout();
        $this->post('/login', ['email' => 'ana@gomez.com', 'password' => 'camion2026']);
        $this->assertAuthenticatedAs($ana);
        $this->get(route('camiones.index'))->assertSee('AD456ZZ')->assertDontSee('AA111AA');
    }

    public function test_una_cuenta_suspendida_no_entra(): void
    {
        $this->actingAs($this->admin())->put(route('admin.cuentas.update', $this->otra), [
            'nombre' => 'Transportes Pérez', 'activa' => '0',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->otra->fresh()->activa);

        // No puede iniciar sesión…
        auth()->logout();
        $this->post('/login', ['email' => $this->otroUsuario->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // …y si tenía la sesión abierta, lo saca en el próximo clic.
        $this->actingAs($this->otroUsuario)->get(route('viajes.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_el_admin_no_puede_suspender_su_propia_cuenta(): void
    {
        $this->actingAs($this->admin())->put(route('admin.cuentas.update', $this->cuenta), [
            'nombre' => 'Cuenta de prueba', 'activa' => '0',
        ])->assertSessionHasErrors('activa');

        $this->assertTrue($this->cuenta->fresh()->activa);
    }

    public function test_el_admin_agrega_usuarios_y_les_cambia_la_contrasena(): void
    {
        $this->actingAs($this->admin())->post(route('admin.cuentas.usuarios.store', $this->otra), [
            'usuario' => 'Secretaria', 'email' => 'papeles@perez.com', 'password' => 'papeles123',
        ])->assertRedirect(route('admin.cuentas.edit', $this->otra));

        $this->assertSame($this->otra->id, User::where('email', 'papeles@perez.com')->value('cuenta_id'));

        $this->actingAs($this->usuario)->put(route('admin.usuarios.contrasena', $this->otroUsuario), [
            'password' => 'nueva-clave-1',
        ])->assertRedirect(route('admin.cuentas.edit', $this->otra));

        auth()->logout();
        $this->post('/login', ['email' => $this->otroUsuario->email, 'password' => 'nueva-clave-1']);
        $this->assertAuthenticatedAs($this->otroUsuario);
    }

    public function test_el_comando_crea_un_usuario_en_su_propia_cuenta(): void
    {
        $this->artisan('usuario:crear', ['nombre' => 'Luis', 'email' => 'luis@ejemplo.com', 'password' => 'secreto123', '--admin' => true])
            ->assertSuccessful();

        $luis = User::where('email', 'luis@ejemplo.com')->firstOrFail();
        $this->assertTrue($luis->esAdmin());
        $this->assertSame('Luis', $luis->cuenta->nombre);
        $this->assertSame(2, MedioPago::withoutGlobalScope('cuenta')->where('cuenta_id', $luis->cuenta_id)->count());
    }
}
