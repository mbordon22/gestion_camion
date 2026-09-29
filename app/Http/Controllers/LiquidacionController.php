<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\ChoferMovimiento;
use App\Models\Liquidacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\CuentaActual;

/**
 * Lo que se le paga al chofer a comisión: sus viajes sin liquidar, menos lo
 * que pidió adelantado, más lo que pagó de su bolsillo.
 *
 * No hay un período fijo. Se marca lo que entra en cada pago (una quincena,
 * una semana, lo que se haya juntado) y lo que no se marca queda para el
 * próximo.
 */
class LiquidacionController extends Controller
{
    public function index(Chofer $chofer)
    {
        $viajes = $chofer->viajes()->comisionSinLiquidar()
            ->with('cliente')
            ->orderBy('fecha')
            ->get();

        $movimientos = $chofer->movimientos()->sinLiquidar()
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $liquidaciones = $chofer->liquidaciones()
            ->withCount('viajes')
            ->withMin('viajes', 'fecha')
            ->withMax('viajes', 'fecha')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        // El camión sólo se pregunta si hay más de uno: el gasto del chofer
        // cuenta en la rentabilidad del camión que manejaba.
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $camionSugerido = $chofer->viajes()->latest('fecha')->value('camion_id');

        return view('choferes.liquidacion', compact(
            'chofer', 'viajes', 'movimientos', 'liquidaciones', 'camiones', 'camionSugerido'
        ));
    }

    public function store(Request $request, Chofer $chofer)
    {
        $validated = $request->validate([
            'fecha'         => 'required|date',
            'viajes'        => 'nullable|array',
            'viajes.*'      => 'integer',
            'movimientos'   => 'nullable|array',
            'movimientos.*' => 'integer',
            'notas'         => 'nullable|string|max:500',
        ]);

        // Sólo lo de este chofer que todavía no se liquidó: un id ajeno o ya
        // pagado se ignora en vez de pisarse.
        $viajes = $chofer->viajes()->comisionSinLiquidar()
            ->whereIn('id', $validated['viajes'] ?? [])
            ->get();

        $movimientos = $chofer->movimientos()->sinLiquidar()
            ->whereIn('id', $validated['movimientos'] ?? [])
            ->get();

        if ($viajes->isEmpty() && $movimientos->isEmpty()) {
            return back()->withInput()->withErrors(['viajes' => 'Marcá al menos un viaje, adelanto o gasto.']);
        }

        $comisiones = round($viajes->sum('comision_monto'), 2);
        $adelantos  = round($movimientos->where('tipo', 'adelanto')->sum('monto'), 2);
        $gastos     = round($movimientos->where('tipo', 'gasto')->sum('monto'), 2);
        $total      = round($comisiones - $adelantos + $gastos, 2);

        // Si lo adelantado pasa lo que se le debe, el chofer quedaría debiendo
        // y eso no tiene dónde anotarse: el adelanto espera a la próxima.
        if ($total < 0) {
            return back()->withInput()->withErrors([
                'viajes' => 'Los adelantos marcados superan lo que se le debe. Dejá algún adelanto para la próxima liquidación.',
            ]);
        }

        $liquidacion = DB::transaction(function () use ($chofer, $validated, $viajes, $movimientos, $comisiones, $adelantos, $gastos, $total) {
            $liquidacion = $chofer->liquidaciones()->create([
                'fecha'      => substr($validated['fecha'], 0, 10),
                'comisiones' => $comisiones,
                'adelantos'  => $adelantos,
                'gastos'     => $gastos,
                'total'      => $total,
                'notas'      => $validated['notas'] ?? null,
            ]);

            $chofer->viajes()->whereIn('id', $viajes->pluck('id'))->update(['liquidacion_id' => $liquidacion->id]);
            $chofer->movimientos()->whereIn('id', $movimientos->pluck('id'))->update(['liquidacion_id' => $liquidacion->id]);

            return $liquidacion;
        });

        return redirect()->route('liquidaciones.show', $liquidacion)->with('success',
            'Liquidación registrada: $ ' . number_format($total, 2, ',', '.') . ' para ' . $chofer->nombre . '.');
    }

    /** El detalle de un pago, para imprimir o mandarle al chofer. */
    public function show(Liquidacion $liquidacion)
    {
        $liquidacion->load([
            'chofer',
            'viajes' => fn ($q) => $q->with('cliente')->orderBy('fecha'),
            'movimientos' => fn ($q) => $q->orderBy('fecha')->orderBy('id'),
        ]);

        return view('choferes.liquidacion-detalle', compact('liquidacion'));
    }

    /** Un pago cargado por error: todo lo que tenía vuelve a figurar como adeudado. */
    public function destroy(Liquidacion $liquidacion)
    {
        $chofer = $liquidacion->chofer;

        DB::transaction(function () use ($liquidacion) {
            $liquidacion->viajes()->update(['liquidacion_id' => null]);
            $liquidacion->movimientos()->update(['liquidacion_id' => null]);
            $liquidacion->delete();
        });

        return redirect()->route('choferes.liquidacion', $chofer)
            ->with('success', 'Se deshizo la liquidación: esos viajes, adelantos y gastos vuelven a figurar como pendientes.');
    }

    public function storeMovimiento(Request $request, Chofer $chofer)
    {
        $validated = $request->validate([
            'tipo'      => ['required', Rule::in(array_keys(ChoferMovimiento::$tipos))],
            'fecha'     => 'required|date',
            'monto'     => 'required|numeric|min:0.01|max:9999999999',
            'concepto'  => 'nullable|string|max:150',
            'camion_id' => ['nullable', CuentaActual::existe('camiones')],
        ], [
            'monto.required' => 'Poné el monto.',
        ]);

        // El camión sólo importa para el gasto: el adelanto no es un costo.
        if ($validated['tipo'] === 'adelanto') {
            $validated['camion_id'] = null;
        } elseif (empty($validated['camion_id'])) {
            $validated['camion_id'] = $chofer->viajes()->latest('fecha')->value('camion_id');
        }

        $chofer->movimientos()->create($validated);

        return redirect()->route('choferes.liquidacion', $chofer)->with('success',
            ($validated['tipo'] === 'adelanto' ? 'Adelanto' : 'Gasto') . ' cargado.');
    }

    public function destroyMovimiento(ChoferMovimiento $movimiento)
    {
        // Lo que ya entró en una liquidación no se borra suelto: primero se
        // deshace la liquidación.
        if ($movimiento->liquidacion_id) {
            return back()->with('error', 'Ya está liquidado. Deshacé esa liquidación primero.');
        }

        $chofer = $movimiento->chofer;
        $movimiento->delete();

        return redirect()->route('choferes.liquidacion', $chofer)->with('success', 'Movimiento eliminado.');
    }
}
