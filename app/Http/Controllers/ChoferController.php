<?php

namespace App\Http\Controllers;

use App\Models\Chofer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\CuentaActual;

class ChoferController extends Controller
{
    public function index()
    {
        // Lo que se le debe a cada uno: comisiones sin liquidar, menos los
        // adelantos, más los gastos que pagó él.
        $choferes = Chofer::withCount('viajes')
            ->withMin('viajes', 'fecha')
            ->withMax('viajes', 'fecha')
            ->withSum(['viajes as comisiones_sin_liquidar' => fn ($q) => $q->comisionSinLiquidar()], 'comision_monto')
            ->withSum(['movimientos as adelantos_sin_liquidar' => fn ($q) => $q->sinLiquidar()->adelantos()], 'monto')
            ->withSum(['movimientos as gastos_sin_liquidar' => fn ($q) => $q->sinLiquidar()->gastos()], 'monto')
            ->withCount('movimientos')
            ->withCount('liquidaciones')
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
        if ($chofer->viajes()->exists() || $chofer->movimientos()->exists() || $chofer->liquidaciones()->exists()) {
            $chofer->update(['activo' => false]);
            return redirect()->route('choferes.index')
                ->with('success', 'El chofer tiene viajes o pagos cargados: se desactivó en lugar de borrarse.');
        }

        $chofer->delete();
        return redirect()->route('choferes.index')->with('success', 'Chofer eliminado.');
    }

    /**
     * Cambiar cómo cobra no toca los viajes ya cargados: cada uno grabó su
     * comisión. Vale para los que se carguen de acá en adelante.
     */
    private function validar(Request $request, ?Chofer $chofer = null): array
    {
        $modalidad = $request->input('modalidad') ?: null;
        $porcentaje = $modalidad === 'porcentaje';

        $validated = $request->validate([
            'nombre'    => ['required', 'string', 'max:100', CuentaActual::unica('choferes', 'nombre')->ignore($chofer)],
            'dni'       => ['nullable', 'string', 'regex:/^\d{1,2}\.?\d{3}\.?\d{3}$/'],
            'telefono'  => 'nullable|string|max:30',
            'modalidad' => ['nullable', Rule::in(array_keys(Chofer::$modalidades))],
            'valor'     => $modalidad
                ? ['required', 'numeric', 'min:0.01', $porcentaje ? 'max:100' : 'max:9999999999']
                : 'nullable',
            'notas'     => 'nullable|string|max:500',
            'activo'    => 'nullable|boolean',
        ], [
            'dni.regex'      => 'El DNI tiene que tener 7 u 8 números, con o sin puntos.',
            'valor.required' => $porcentaje ? 'Poné qué porcentaje se lleva el chofer.' : 'Poné cuánto se lleva el chofer por viaje.',
            'valor.max'      => 'El porcentaje no puede pasar de 100.',
        ]);

        $validated['dni']       = Chofer::formatearDni($validated['dni'] ?? null);
        $validated['activo']    = $request->boolean('activo');
        $validated['modalidad'] = $modalidad;

        // Sin comisión no hay valor que guardar.
        if (! $modalidad) {
            $validated['valor'] = null;
        }

        // Si la cuenta no usa comisiones, el formulario no las muestra: lo
        // que el chofer ya tenía queda como estaba.
        if (! CuentaActual::usa('comisiones')) {
            unset($validated['modalidad'], $validated['valor']);
        }

        return $validated;
    }
}
