<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Viaje;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;

class CamionController extends Controller
{
    public function index()
    {
        $camiones = Camion::orderBy('patente')->get();
        return view('camiones.index', compact('camiones'));
    }

    public function create()
    {
        return view('camiones.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        Camion::create($validated);

        return redirect()->route('camiones.index')->with('success', 'Camión creado correctamente.');
    }

    public function edit(Camion $camion)
    {
        return view('camiones.edit', compact('camion'));
    }

    public function update(Request $request, Camion $camion)
    {
        $validated = $this->validar($request, $camion);
        $camion->update($validated);

        return redirect()->route('camiones.index')->with('success', 'Camión actualizado correctamente.');
    }

    public function destroy(Camion $camion)
    {
        $enUso = Viaje::where('camion_id', $camion->id)->exists()
            || Combustible::where('camion_id', $camion->id)->exists()
            || Mantenimiento::where('camion_id', $camion->id)->exists();

        if ($enUso) {
            $camion->update(['activo' => false]);
            return redirect()->route('camiones.index')
                ->with('success', 'El camión tiene viajes o gastos cargados: se desactivó en lugar de borrarse.');
        }

        $camion->delete();
        return redirect()->route('camiones.index')->with('success', 'Camión eliminado.');
    }

    private function validar(Request $request, ?Camion $camion = null): array
    {
        $validated = $request->validate([
            'patente'       => 'required|string|max:20|unique:camiones,patente,' . ($camion->id ?? 'NULL'),
            'marca'         => 'nullable|string|max:100',
            'modelo'        => 'nullable|string|max:100',
            'anio'          => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'activo'        => 'nullable|boolean',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
