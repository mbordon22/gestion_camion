<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Camion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ViajeController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);
        $camionId = $request->get('camion_id');

        $viajes = Viaje::with('camion')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalPeriodo = $viajes->sum('total');
        $cantidadViajes = $viajes->count();

        $cobrados   = $viajes->where('cobrado', true);
        $noCobrados = $viajes->where('cobrado', false);

        $totalCobrado      = $cobrados->sum('total');
        $totalNoCobrado    = $noCobrados->sum('total');
        $cantidadCobrados  = $cobrados->count();
        $cantidadNoCobrados = $noCobrados->count();

        $camiones = Camion::orderBy('patente')->get();

        return view('viajes.index', compact(
            'viajes', 'totalPeriodo', 'cantidadViajes', 'periodo', 'desde', 'hasta',
            'totalCobrado', 'totalNoCobrado', 'cantidadCobrados', 'cantidadNoCobrados',
            'camiones', 'camionId'
        ));
    }

    public function create()
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('viajes.create', compact('camiones'));
    }

    public function store(Request $request)
    {
        Viaje::create($this->validar($request));

        return redirect()->route('viajes.index')->with('success', 'Viaje registrado correctamente.');
    }

    public function edit(Viaje $viaje)
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('viajes.edit', compact('viaje', 'camiones'));
    }

    public function update(Request $request, Viaje $viaje)
    {
        $viaje->update($this->validar($request));

        return redirect()->route('viajes.index')->with('success', 'Viaje actualizado correctamente.');
    }

    public function destroy(Viaje $viaje)
    {
        $viaje->delete();
        return redirect()->route('viajes.index')->with('success', 'Viaje eliminado.');
    }

    public function toggleCobrado(Request $request, Viaje $viaje)
    {
        $viaje->update(['cobrado' => ! $viaje->cobrado]);

        if ($request->wantsJson()) {
            return response()->json([
                'cobrado' => $viaje->cobrado,
                'total'   => (float) $viaje->total,
            ]);
        }

        return back()->with('success', 'Estado de cobro actualizado.');
    }

    /**
     * Valida el viaje y resuelve el total del lado del servidor:
     *  - modo 'fijo'     -> el total es el que escribió el usuario;
     *  - modo 'cantidad' -> el total es cantidad x precio_unitario.
     */
    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'camion_id'       => 'required|exists:camiones,id',
            'modo_cobro'      => 'required|in:fijo,cantidad',
            'fecha'           => 'required|date',
            'fecha_carga'     => 'nullable|date',
            'cantidad'        => 'required_if:modo_cobro,cantidad|nullable|numeric|min:0.01',
            'unidad'          => 'required_if:modo_cobro,cantidad|nullable|string|max:20',
            'precio_unitario' => 'required_if:modo_cobro,cantidad|nullable|numeric|min:0',
            'total'           => 'required_if:modo_cobro,fijo|nullable|numeric|min:0',
            'cobrado'         => 'boolean',
            'origen'          => 'nullable|string|max:100',
            'destino'         => 'nullable|string|max:100',
            'km_recorridos'   => 'nullable|integer|min:0',
            'observaciones'   => 'nullable|string|max:500',
        ]);

        if ($validated['modo_cobro'] === 'fijo') {
            $validated['cantidad']        = null;
            $validated['unidad']          = null;
            $validated['precio_unitario'] = null;
        } else {
            $validated['total'] = round($validated['cantidad'] * $validated['precio_unitario'], 2);
        }

        $validated['cobrado'] = $request->boolean('cobrado');

        return $validated;
    }

    private function rangoFechas(string $periodo, Request $request): array
    {
        $hoy = Carbon::today();

        return match ($periodo) {
            'hoy'      => [$hoy->toDateString(), $hoy->toDateString()],
            'semana'   => [$hoy->startOfWeek()->toDateString(), $hoy->copy()->endOfWeek()->toDateString()],
            'quincena' => $hoy->day <= 15
                ? [$hoy->startOfMonth()->toDateString(), $hoy->copy()->startOfMonth()->addDays(14)->toDateString()]
                : [$hoy->copy()->startOfMonth()->addDays(15)->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
            'rango'    => [
                $request->get('desde', $hoy->copy()->subDays(60)->toDateString()),
                $request->get('hasta', $hoy->toDateString()),
            ],
            default    => [$hoy->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
        };
    }
}
