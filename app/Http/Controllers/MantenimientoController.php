<?php

namespace App\Http\Controllers;

use App\Models\Mantenimiento;
use App\Models\MedioPago;
use App\Models\Camion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $camionId = $request->get('camion_id');

        $registros = Mantenimiento::with(['medioPago', 'camion'])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
        $proximoService = Mantenimiento::whereNotNull('proximo_service')->orderByDesc('id')->value('proximo_service');

        $camiones = Camion::orderBy('patente')->get();

        return view('mantenimiento.index', compact('registros', 'proximoService', 'camiones', 'camionId'));
    }

    public function create()
    {
        $tipos = Mantenimiento::$tipos;
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('mantenimiento.create', compact('tipos', 'mediosPago', 'camiones'));
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

        Mantenimiento::create($validated);

        return redirect()->route('mantenimiento.index')->with('success', 'Mantenimiento registrado correctamente.');
    }

    public function edit(Mantenimiento $mantenimiento)
    {
        $tipos = Mantenimiento::$tipos;
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        return view('mantenimiento.edit', compact('mantenimiento', 'tipos', 'mediosPago', 'camiones'));
    }

    public function update(Request $request, Mantenimiento $mantenimiento)
    {
        $validated = $this->validar($request);
        $medio = ! empty($validated['medio_pago_id']) ? MedioPago::find($validated['medio_pago_id']) : null;

        if (empty($validated['fecha_vencimiento']) && $medio && $medio->requiereFechaPagoManual()) {
            return back()->withInput()->withErrors([
                'fecha_vencimiento' => 'Indicá la fecha de pago de este gasto a crédito.',
            ]);
        }

        $validated['fecha_vencimiento'] = $this->resolverFechaVencimiento($validated, $medio);

        $mantenimiento->update($validated);

        return redirect()->route('mantenimiento.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy(Mantenimiento $mantenimiento)
    {
        $mantenimiento->delete();
        return redirect()->route('mantenimiento.index')->with('success', 'Registro eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'camion_id'         => 'required|exists:camiones,id',
            'fecha'             => 'required|date',
            'tipo'              => 'required|in:aceite,filtros,neumaticos,frenos,repuesto,service,otro',
            'monto'             => 'required|numeric|min:0',
            'km_actuales'       => 'nullable|integer|min:0',
            'proximo_service'   => 'nullable|integer|min:0',
            'detalle'           => 'nullable|string|max:500',
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
}
