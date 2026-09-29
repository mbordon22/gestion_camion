<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use Illuminate\Http\Request;

/**
 * Qué usa la cuenta de lo avanzado (Cuenta::FUNCIONES). La cambia
 * cualquiera de sus usuarios: es para que el sistema muestre sólo lo que
 * les sirve, no un permiso.
 */
class ConfiguracionController extends Controller
{
    public function edit(Request $request)
    {
        return view('configuracion.edit', ['cuenta' => $request->user()->cuenta]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'funciones'   => 'nullable|array',
            'funciones.*' => 'nullable|string',
        ]);

        $request->user()->cuenta->update([
            'funciones' => Cuenta::funcionesValidas($request->input('funciones')),
        ]);

        return redirect()->route('configuracion.edit')->with('success', 'Configuración guardada.');
    }
}
