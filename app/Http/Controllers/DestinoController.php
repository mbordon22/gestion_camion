<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestinoController extends Controller
{
    public function index()
    {
        $destinos = Destino::with('cliente')
            ->withCount('viajes')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('destinos.index', compact('destinos'));
    }

    public function create()
    {
        return view('destinos.create', ['clientes' => $this->clientes()]);
    }

    public function store(Request $request)
    {
        Destino::create($this->validar($request));

        return redirect()->route('destinos.index')->with('success', 'Destino creado correctamente.');
    }

    public function edit(Destino $destino)
    {
        return view('destinos.edit', ['destino' => $destino, 'clientes' => $this->clientes()]);
    }

    public function update(Request $request, Destino $destino)
    {
        $destino->update($this->validar($request, $destino));

        return redirect()->route('destinos.index')->with('success', 'Destino actualizado correctamente.');
    }

    public function destroy(Destino $destino)
    {
        // El viaje guarda el destino como texto: borrarlo del catálogo no toca
        // los viajes, pero igual se desactiva para no perder los km cargados.
        if ($destino->viajes()->exists()) {
            $destino->update(['activo' => false]);
            return redirect()->route('destinos.index')
                ->with('success', 'El destino tiene viajes cargados: se desactivó en lugar de borrarse.');
        }

        $destino->delete();
        return redirect()->route('destinos.index')->with('success', 'Destino eliminado.');
    }

    private function validar(Request $request, ?Destino $destino = null): array
    {
        $clienteId = $request->input('cliente_id') ?: null;

        $validated = $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'nombre'     => [
                'required', 'string', 'max:100',
                // El mismo nombre puede repetirse entre clientes distintos.
                Rule::unique('destinos', 'nombre')
                    ->where(fn ($q) => $q->where('cliente_id', $clienteId))
                    ->ignore($destino),
            ],
            'origen'     => 'nullable|string|max:100',
            'km'         => 'nullable|integer|min:0|max:5000',
            'notas'      => 'nullable|string|max:500',
            'activo'     => 'nullable|boolean',
        ], [
            'nombre.unique' => 'Ya tenés un destino con ese nombre para ese cliente.',
        ]);

        $validated['cliente_id'] = $clienteId;
        $validated['activo']     = $request->boolean('activo');

        return $validated;
    }

    private function clientes()
    {
        return Cliente::where('activo', true)->orderBy('nombre')->get();
    }
}
