<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ViajeController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);

        $viajes = Viaje::whereBetween('fecha', [$desde, $hasta])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalPeriodo = $viajes->sum('total');
        $cantidadViajes = $viajes->count();

        $facturados    = $viajes->where('facturado', true);
        $noFacturados  = $viajes->where('facturado', false);

        $totalFacturado      = $facturados->sum('total');
        $totalNoFacturado    = $noFacturados->sum('total');
        $cantidadFacturados  = $facturados->count();
        $cantidadNoFacturados = $noFacturados->count();

        return view('viajes.index', compact(
            'viajes', 'totalPeriodo', 'cantidadViajes', 'periodo', 'desde', 'hasta',
            'totalFacturado', 'totalNoFacturado', 'cantidadFacturados', 'cantidadNoFacturados'
        ));
    }

    public function create()
    {
        return view('viajes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha'        => 'required|date',
            'fecha_carga'  => 'nullable|date',
            'nro_ingreso'  => 'nullable|string|max:50',
            'tipo_ingreso' => 'nullable|string|max:100',
            'motivo'       => 'nullable|string|max:255',
            'bolsas'       => 'required|integer|min:1',
            'precio_bolsa' => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
            'facturado'    => 'boolean',
            'kg_netos'     => 'nullable|numeric|min:0',
            'destino'      => 'nullable|string|max:100',
            'observaciones'=> 'nullable|string|max:500',
        ]);

        $validated['facturado'] = $request->boolean('facturado');

        Viaje::create($validated);

        return redirect()->route('viajes.index')->with('success', 'Viaje registrado correctamente.');
    }

    public function edit(Viaje $viaje)
    {
        return view('viajes.edit', compact('viaje'));
    }

    public function update(Request $request, Viaje $viaje)
    {
        $validated = $request->validate([
            'fecha'        => 'required|date',
            'fecha_carga'  => 'nullable|date',
            'nro_ingreso'  => 'nullable|string|max:50',
            'tipo_ingreso' => 'nullable|string|max:100',
            'motivo'       => 'nullable|string|max:255',
            'bolsas'       => 'required|integer|min:1',
            'precio_bolsa' => 'required|numeric|min:0',
            'total'        => 'required|numeric|min:0',
            'facturado'    => 'boolean',
            'kg_netos'     => 'nullable|numeric|min:0',
            'destino'      => 'nullable|string|max:100',
            'observaciones'=> 'nullable|string|max:500',
        ]);

        $validated['facturado'] = $request->boolean('facturado');

        $viaje->update($validated);

        return redirect()->route('viajes.index')->with('success', 'Viaje actualizado correctamente.');
    }

    public function destroy(Viaje $viaje)
    {
        $viaje->delete();
        return redirect()->route('viajes.index')->with('success', 'Viaje eliminado.');
    }

    public function toggleFacturado(Request $request, Viaje $viaje)
    {
        $viaje->update(['facturado' => ! $viaje->facturado]);

        if ($request->wantsJson()) {
            return response()->json([
                'facturado' => $viaje->facturado,
                'total'     => (float) $viaje->total,
            ]);
        }

        return back()->with('success', 'Estado de facturación actualizado.');
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
