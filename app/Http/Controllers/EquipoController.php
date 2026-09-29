<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\CuentaActual;

class EquipoController extends Controller
{
    public function index()
    {
        $equipos = Equipo::with('camion')
            ->withCount('viajes')
            ->withCount(['viajes as viajes_sin_pagar' => fn ($q) => $q->alquilerSinPagar()])
            ->withSum(['viajes as sin_pagar' => fn ($q) => $q->alquilerSinPagar()], 'alquiler_monto')
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();

        return view('equipos.index', compact('equipos'));
    }

    public function create()
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();

        return view('equipos.create', compact('camiones'));
    }

    public function store(Request $request)
    {
        Equipo::create($this->validar($request));

        return redirect()->route('equipos.index')->with('success', 'Equipo creado correctamente.');
    }

    public function edit(Equipo $equipo)
    {
        $camiones = Camion::where('activo', true)
            ->when($equipo->camion_id, fn ($q, $id) => $q->orWhere('id', $id))
            ->orderBy('patente')
            ->get();

        return view('equipos.edit', compact('equipo', 'camiones'));
    }

    /**
     * Cambiar el acuerdo no toca los viajes ya cargados: cada uno grabó su
     * monto. Vale para los que se carguen de acá en adelante.
     */
    public function update(Request $request, Equipo $equipo)
    {
        $equipo->update($this->validar($request, $equipo));

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo)
    {
        if ($equipo->viajes()->exists()) {
            $equipo->update(['activo' => false]);
            return redirect()->route('equipos.index')
                ->with('success', 'El equipo tiene viajes cargados: se desactivó en lugar de borrarse.');
        }

        $equipo->delete();
        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado.');
    }

    /**
     * Lo que se le debe al dueño del equipo, viaje por viaje, y los pagos que
     * ya se le hicieron. Un pago es el conjunto de viajes marcados el mismo día.
     */
    public function pagos(Equipo $equipo)
    {
        $pendientes = $equipo->viajes()->alquilerSinPagar()
            ->with('cliente')
            ->orderBy('fecha')
            ->get();

        $pagos = $equipo->viajes()->whereNotNull('alquiler_pagado_el')
            ->orderByDesc('alquiler_pagado_el')
            ->get()
            ->groupBy(fn ($viaje) => $viaje->alquiler_pagado_el->toDateString())
            ->map(fn ($grupo, $dia) => (object) [
                'fecha'  => $grupo->first()->alquiler_pagado_el,
                'dia'    => $dia,
                'viajes' => $grupo->count(),
                'desde'  => $grupo->min('fecha'),
                'hasta'  => $grupo->max('fecha'),
                'monto'  => $grupo->sum('alquiler_monto'),
            ])
            ->values();

        return view('equipos.pagos', compact('equipo', 'pendientes', 'pagos'));
    }

    public function registrarPago(Request $request, Equipo $equipo)
    {
        $validated = $request->validate([
            'fecha'    => 'required|date',
            'viajes'   => 'required|array|min:1',
            'viajes.*' => 'integer',
        ], [
            'viajes.required' => 'Marcá al menos un viaje.',
        ]);

        // Sólo los de este equipo que todavía se deben: un id de otro equipo
        // o uno ya pagado se ignora en vez de pisarse.
        $viajes = $equipo->viajes()->alquilerSinPagar()->whereIn('id', $validated['viajes']);
        $monto = (clone $viajes)->sum('alquiler_monto');
        $cantidad = $viajes->update(['alquiler_pagado_el' => substr($validated['fecha'], 0, 10)]);

        return redirect()->route('equipos.pagos', $equipo)->with('success',
            "Pago registrado: {$cantidad} viaje" . ($cantidad !== 1 ? 's' : '') .
            ' por $ ' . number_format($monto, 2, ',', '.') . '.');
    }

    /** Vuelve a dejar como adeudados los viajes de un pago cargado por error. */
    public function deshacerPago(Equipo $equipo, string $fecha)
    {
        $equipo->viajes()->whereDate('alquiler_pagado_el', $fecha)->update(['alquiler_pagado_el' => null]);

        return redirect()->route('equipos.pagos', $equipo)->with('success', 'Se deshizo el pago: esos viajes vuelven a figurar como adeudados.');
    }

    private function validar(Request $request, ?Equipo $equipo = null): array
    {
        $alquilado = $request->boolean('alquilado');
        $porcentaje = $request->input('modalidad') === 'porcentaje';

        $validated = $request->validate([
            'nombre'      => ['required', 'string', 'max:100', CuentaActual::unica('equipos', 'nombre')->ignore($equipo)],
            'tipo'        => 'nullable|string|max:40',
            'patente'     => 'nullable|string|max:15',
            'camion_id'   => ['nullable', CuentaActual::existe('camiones')],
            'alquilado'   => 'nullable|boolean',
            'propietario' => 'nullable|string|max:100',
            'modalidad'   => $alquilado ? ['required', Rule::in(array_keys(Equipo::$modalidades))] : 'nullable',
            'valor'       => $alquilado
                ? ['required', 'numeric', 'min:0.01', $porcentaje ? 'max:100' : 'max:9999999999']
                : 'nullable',
            'notas'       => 'nullable|string|max:500',
            'activo'      => 'nullable|boolean',
        ], [
            'valor.required' => $porcentaje ? 'Poné qué porcentaje se lleva el dueño.' : 'Poné cuánto se lleva el dueño por viaje.',
            'valor.max'      => 'El porcentaje no puede pasar de 100.',
        ]);

        $validated['alquilado'] = $alquilado;
        $validated['activo'] = $request->boolean('activo');
        $validated['patente'] = isset($validated['patente'])
            ? strtoupper(str_replace(' ', '', $validated['patente'])) ?: null
            : null;

        // Un equipo propio no tiene acuerdo que guardar.
        if (! $alquilado) {
            $validated['propietario'] = null;
            $validated['modalidad'] = null;
            $validated['valor'] = null;
        }

        return $validated;
    }
}
