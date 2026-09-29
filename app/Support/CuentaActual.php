<?php

namespace App\Support;

use App\Models\Cuenta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * De qué cuenta son los datos que se leen y se crean: la del usuario que
 * entró. Todo modelo con DeLaCuenta filtra y completa por ella.
 *
 * Sin nadie logueado (consola, tinker, migraciones) no hay cuenta y no se
 * filtra nada. Los tests fijan una con porDefecto() para crear sus datos
 * antes de loguearse.
 */
class CuentaActual
{
    private static ?int $porDefecto = null;

    public static function id(): ?int
    {
        return Auth::user()?->cuenta_id ?? self::$porDefecto;
    }

    public static function cuenta(): ?Cuenta
    {
        if ($usuario = Auth::user()) {
            return $usuario->cuenta;
        }

        return self::$porDefecto ? Cuenta::find(self::$porDefecto) : null;
    }

    /**
     * Si la cuenta usa alguna de esas funciones (Cuenta::FUNCIONES). Sin
     * cuenta (consola) no se oculta nada.
     */
    public static function usa(string ...$funciones): bool
    {
        $cuenta = self::cuenta();

        if (! $cuenta) {
            return true;
        }

        foreach ($funciones as $funcion) {
            if ($cuenta->usa($funcion)) {
                return true;
            }
        }

        return false;
    }

    /** La cuenta a usar cuando no hay nadie logueado (tests y procesos internos). */
    public static function porDefecto(?int $cuentaId): void
    {
        self::$porDefecto = $cuentaId;
    }

    /** Regla de validación: que el id exista, y en esta cuenta. */
    public static function existe(string $tabla, string $columna = 'id'): Exists
    {
        return Rule::exists($tabla, $columna)->where('cuenta_id', self::id());
    }

    /** Regla de validación: que el valor no se repita dentro de esta cuenta. */
    public static function unica(string $tabla, string $columna): Unique
    {
        return Rule::unique($tabla, $columna)->where('cuenta_id', self::id());
    }
}
