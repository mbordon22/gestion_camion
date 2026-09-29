<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un usuario sin cuenta, o con la cuenta suspendida, no sigue adentro: si
 * la suspendieron con la sesión abierta, lo saca en el próximo clic.
 */
class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario && ! $usuario->cuenta?->activa) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está suspendida. Comunicate con el administrador.',
            ]);
        }

        return $next($request);
    }
}
