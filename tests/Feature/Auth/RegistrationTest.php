<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * No hay registro público: las cuentas las crea el administrador desde su
 * panel (Admin\CuentaController).
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_hay_pantalla_de_registro(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_nadie_se_puede_registrar_solo(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertFalse(User::where('email', 'test@example.com')->exists());
    }
}
