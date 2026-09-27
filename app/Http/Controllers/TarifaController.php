<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Tarifa;
use App\Models\Viaje;
use Illuminate\Http\Request;

class TarifaController extends Controller
{
    public function index()
    {
        $tarifas = Tarifa::with('cliente')
            ->orderBy('cliente_id')
            ->orderBy('producto')
            ->orderBy('km_desde')
            ->orderByDesc('vigente_desde')
            ->get()
            ->groupBy(fn (Tarifa $tarifa) => $tarifa->cliente?->nombre ?? 'Sin cliente');

        return view('tarifas.index', compact('tarifas'));
    }

    public function create()
    {
        return view('tarifas.create', $this->datosDelFormulario());
    }

    public function store(Request $request)
    {
        Tarifa::create($this->validar($request));

        return redirect()->route('tarifas.index')->with('success', 'Tarifa creada correctamente.');
    }

    public function edit(Tarifa $tarifa)
    {
        return view('tarifas.edit', $this->datosDelFormulario() + ['tarifa' => $tarifa]);
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $tarifa->update($this->validar($request, $tarifa));

        return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada correctamente.');
    }

    public function destroy(Tarifa $tarifa)
    {
        // Se puede borrar sin miedo: el total quedó grabado en cada viaje.
        $tarifa->delete();

        return redirect()->route('tarifas.index')->with('success', 'Tarifa eliminada.');
    }

    /**
     * La tarifa que le corresponde a lo que hay cargado en el formulario de
     * viaje. La consulta el navegador para proponer el precio por unidad; es
     * una sugerencia, el usuario puede cobrar otra cosa.
     */
    public function sugerir(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => 'nullable|integer',
            'producto'   => 'nullable|string|max:60',
            'km'         => 'nullable|integer|min:0',
            'fecha'      => 'nullable|date',
        ]);

        $tarifa = Tarifa::paraViaje(
            $datos['cliente_id'] ?? null,
            $datos['producto'] ?? null,
            isset($datos['km']) ? (int) $datos['km'] : null,
            $datos['fecha'] ?? now(),
        );

        if (! $tarifa) {
            return response()->json(['tarifa' => null]);
        }

        return response()->json(['tarifa' => [
            'importe' => (float) $tarifa->importe,
            'unidad'  => $tarifa->unidad,
            'detalle' => $tarifa->detalle(),
        ]]);
    }

    private function validar(Request $request, ?Tarifa $tarifa = null): array
    {
        $validated = $request->validate([
            'cliente_id'    => 'required|exists:clientes,id',
            'producto'      => 'nullable|string|max:60',
            'km_desde'      => 'required|integer|min:0|max:5000',
            'km_hasta'      => 'required|integer|min:0|max:5000|gte:km_desde',
            'importe'       => 'required|numeric|min:0',
            'unidad'        => 'required|string|max:20',
            'vigente_desde' => 'required|date',
            'notas'         => 'nullable|string|max:500',
        ], [
            'km_hasta.gte' => 'El rango termina antes de donde empieza.',
        ]);

        $validated['producto'] = trim((string) $validated['producto']) ?: null;

        $this->rechazarRangoPisado($request, $validated, $tarifa);

        return $validated;
    }

    /**
     * Dos tarifas del mismo cliente, producto y vigencia no pueden cubrir el
     * mismo kilómetro: no habría forma de saber cuál aplicar. Rangos pisados
     * entre vigencias distintas sí valen, que es como se actualiza un precio.
     */
    private function rechazarRangoPisado(Request $request, array $datos, ?Tarifa $tarifa): void
    {
        $pisada = Tarifa::where('cliente_id', $datos['cliente_id'])
            ->where('producto', $datos['producto'])
            ->whereDate('vigente_desde', $datos['vigente_desde'])
            ->where('km_desde', '<=', $datos['km_hasta'])
            ->where('km_hasta', '>=', $datos['km_desde'])
            ->when($tarifa, fn ($q) => $q->where('id', '!=', $tarifa->id))
            ->first();

        if ($pisada) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'km_desde' => "Ese rango se pisa con el de {$pisada->rango()}, que rige desde la misma fecha.",
            ]);
        }
    }

    private function datosDelFormulario(): array
    {
        return [
            'clientes'  => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Viaje::productosSugeridos(),
            'unidades'  => Viaje::$unidades,
        ];
    }
}
