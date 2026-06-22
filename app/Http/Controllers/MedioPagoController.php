<?php

namespace App\Http\Controllers;

use App\Models\MedioPago;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use App\Models\Prestamo;
use Illuminate\Http\Request;

class MedioPagoController extends Controller
{
    public function index()
    {
        $medios = MedioPago::orderBy('nombre')->get();
        return view('medios_pago.index', compact('medios'));
    }

    public function create()
    {
        $tipos = MedioPago::$tipos;
        return view('medios_pago.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        MedioPago::create($validated);

        return redirect()->route('medios-pago.index')->with('success', 'Medio de pago creado correctamente.');
    }

    public function edit(MedioPago $medioPago)
    {
        $tipos = MedioPago::$tipos;
        return view('medios_pago.edit', compact('medioPago', 'tipos'));
    }

    public function update(Request $request, MedioPago $medioPago)
    {
        $validated = $this->validar($request);
        $medioPago->update($validated);

        return redirect()->route('medios-pago.index')->with('success', 'Medio de pago actualizado correctamente.');
    }

    public function destroy(MedioPago $medioPago)
    {
        $enUso = Combustible::where('medio_pago_id', $medioPago->id)->exists()
            || Mantenimiento::where('medio_pago_id', $medioPago->id)->exists()
            || Prestamo::where('medio_pago_id', $medioPago->id)->exists();

        if ($enUso) {
            $medioPago->update(['activo' => false]);
            return redirect()->route('medios-pago.index')
                ->with('success', 'El medio se usó en gastos o préstamos: se desactivó en lugar de borrarse.');
        }

        $medioPago->delete();
        return redirect()->route('medios-pago.index')->with('success', 'Medio de pago eliminado.');
    }

    private function validar(Request $request): array
    {
        $validated = $request->validate([
            'nombre'          => 'required|string|max:100',
            'tipo'            => 'required|in:efectivo,debito,transferencia,credito',
            'dia_cierre'      => 'nullable|integer|min:1|max:31',
            'dia_vencimiento' => 'nullable|integer|min:1|max:31',
            'activo'          => 'nullable|boolean',
        ]);

        // Solo el tipo crédito usa cierre/vencimiento
        if ($validated['tipo'] !== 'credito') {
            $validated['dia_cierre'] = null;
            $validated['dia_vencimiento'] = null;
        }

        $validated['activo'] = $request->boolean('activo');

        return $validated;
    }
}
