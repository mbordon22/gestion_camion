<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\CuentaActual;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::withCount('viajes')
            ->withSum(['viajes as sin_cobrar' => fn ($q) => $q->where('cobrado', false)], 'total')
            ->withMin('viajes', 'fecha')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        Cliente::create($this->validar($request));

        return redirect()->route('clientes.index')->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->viajes()->exists()) {
            $cliente->update(['activo' => false]);
            return redirect()->route('clientes.index')
                ->with('success', 'El cliente tiene viajes cargados: se desactivó en lugar de borrarse.');
        }

        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        $validated = $request->validate([
            'nombre'   => ['required', 'string', 'max:100', CuentaActual::unica('clientes', 'nombre')->ignore($cliente)],
            'cuit'     => ['nullable', 'string', 'regex:/^\d{2}-?\d{8}-?\d$/'],
            'telefono' => 'nullable|string|max:30',
            'notas'    => 'nullable|string|max:500',
            'activo'   => 'nullable|boolean',
        ], [
            'cuit.regex' => 'El CUIT tiene que tener 11 números, con o sin guiones.',
        ]);

        $validated['cuit']   = Cliente::formatearCuit($validated['cuit'] ?? null);
        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
