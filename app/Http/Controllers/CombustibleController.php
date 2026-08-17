<?php

namespace App\Http\Controllers;

use App\Models\Combustible;
use App\Models\MedioPago;
use App\Models\Camion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CombustibleController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);
        $camionId = $request->get('camion_id');

        $registros = Combustible::with(['medioPago', 'camion'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalLitros = $registros->sum('litros');
        $totalGasto  = $registros->sum('total');

        $camiones = Camion::orderBy('patente')->get();

        return view('combustible.index', compact('registros', 'totalLitros', 'totalGasto', 'periodo', 'desde', 'hasta', 'camiones', 'camionId'));
    }

    public function create()
    {
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('combustible.create', compact('mediosPago', 'camiones'));
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $medio = ! empty($validated['medio_pago_id']) ? MedioPago::find($validated['medio_pago_id']) : null;

        if (empty($validated['fecha_vencimiento']) && $medio && $medio->requiereFechaPagoManual()) {
            return back()->withInput()->withErrors([
                'fecha_vencimiento' => 'Indicá la fecha de pago de este gasto a crédito.',
            ]);
        }

        $validated['fecha_vencimiento'] = $this->resolverFechaVencimiento($validated, $medio);

        Combustible::create($validated);

        return redirect()->route('combustible.index')->with('success', 'Carga de combustible registrada correctamente.');
    }

    public function edit(Combustible $combustible)
    {
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('combustible.edit', compact('combustible', 'mediosPago', 'camiones'));
    }

    public function update(Request $request, Combustible $combustible)
    {
        $validated = $this->validar($request);
        $medio = ! empty($validated['medio_pago_id']) ? MedioPago::find($validated['medio_pago_id']) : null;

        if (empty($validated['fecha_vencimiento']) && $medio && $medio->requiereFechaPagoManual()) {
            return back()->withInput()->withErrors([
                'fecha_vencimiento' => 'Indicá la fecha de pago de este gasto a crédito.',
            ]);
        }

        $validated['fecha_vencimiento'] = $this->resolverFechaVencimiento($validated, $medio);

        $combustible->update($validated);

        return redirect()->route('combustible.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Combustible $combustible)
    {
        $combustible->delete();
        return redirect()->route('combustible.index')->with('success', 'Registro eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'camion_id'         => 'required|exists:camiones,id',
            'fecha'             => 'required|date',
            'litros'            => 'required|numeric|min:0',
            'precio_litro'      => 'required|numeric|min:0',
            'total'             => 'required|numeric|min:0',
            'km_odometro'       => 'nullable|integer|min:0',
            'lugar'             => 'nullable|string|max:100',
            'medio_pago_id'     => 'nullable|exists:medios_pago,id',
            'fecha_vencimiento' => 'nullable|date',
        ]);
    }

    /**
     * Si el usuario cargó la fecha de pago, se respeta. Si la dejó vacía:
     * - crédito con cierre/vencimiento -> se autosugiere la fecha;
     * - cualquier otro caso -> se paga el mismo día del gasto (contado).
     */
    private function resolverFechaVencimiento(array $validated, ?MedioPago $medio): string
    {
        if (! empty($validated['fecha_vencimiento'])) {
            return $validated['fecha_vencimiento'];
        }

        if ($medio && $medio->puedeSugerirFecha()) {
            return $medio->fechaCobro(Carbon::parse($validated['fecha']))->toDateString();
        }

        return Carbon::parse($validated['fecha'])->toDateString();
    }

    private function rangoFechas(string $periodo, Request $request): array
    {
        $hoy = Carbon::today();

        return match ($periodo) {
            'hoy'    => [$hoy->toDateString(), $hoy->toDateString()],
            'semana' => [$hoy->startOfWeek()->toDateString(), $hoy->copy()->endOfWeek()->toDateString()],
            'rango'  => [
                $request->get('desde', $hoy->copy()->subDays(60)->toDateString()),
                $request->get('hasta', $hoy->toDateString()),
            ],
            default  => [$hoy->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
        };
    }
}
