<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChoferController extends Controller
{
    public function index()
    {
        $choferes = Chofer::withCount('viajes')
            ->withMin('viajes', 'fecha')
            ->withMax('viajes', 'fecha')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('choferes.index', compact('choferes'));
    }

    public function create()
    {
        return view('choferes.create');
    }

    public function store(Request $request)
    {
        Chofer::create($this->validar($request));

        return redirect()->route('choferes.index')->with('success', 'Chofer creado correctamente.');
    }

    public function edit(Chofer $chofer)
    {
        return view('choferes.edit', compact('chofer'));
    }

    public function update(Request $request, Chofer $chofer)
    {
        $chofer->update($this->validar($request, $chofer));

        return redirect()->route('choferes.index')->with('success', 'Chofer actualizado correctamente.');
    }

    public function destroy(Chofer $chofer)
    {
        if ($chofer->viajes()->exists()) {
            $chofer->update(['activo' => false]);
            return redirect()->route('choferes.index')
                ->with('success', 'El chofer tiene viajes cargados: se desactivó en lugar de borrarse.');
        }

        $chofer->delete();
        return redirect()->route('choferes.index')->with('success', 'Chofer eliminado.');
    }

    private function validar(Request $request, ?Chofer $chofer = null): array
    {
        $validated = $request->validate([
            'nombre'   => ['required', 'string', 'max:100', Rule::unique('choferes', 'nombre')->ignore($chofer)],
            'dni'      => ['nullable', 'string', 'regex:/^\d{1,2}\.?\d{3}\.?\d{3}$/'],
            'telefono' => 'nullable|string|max:30',
            'notas'    => 'nullable|string|max:500',
            'activo'   => 'nullable|boolean',
        ], [
            'dni.regex' => 'El DNI tiene que tener 7 u 8 números, con o sin puntos.',
        ]);

        $validated['dni']    = Chofer::formatearDni($validated['dni'] ?? null);
        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
