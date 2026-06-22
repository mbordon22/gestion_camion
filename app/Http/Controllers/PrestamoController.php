<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Cuota;
use App\Models\MedioPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PrestamoController extends Controller
{
    public function index()
    {
        $prestamos = Prestamo::with('cuotas', 'medioPago')->orderByDesc('id')->get();
        return view('prestamos.index', compact('prestamos'));
    }

    public function create()
    {
        $categorias = Prestamo::$categorias;
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        return view('prestamos.create', compact('categorias', 'mediosPago'));
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);

        DB::transaction(function () use ($validated) {
            $prestamo = Prestamo::create($validated);
            $this->generarCuotas($prestamo);
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo registrado y cuotas generadas.');
    }

    public function edit(Prestamo $prestamo)
    {
        $prestamo->load('cuotas');
        $categorias = Prestamo::$categorias;
        $mediosPago = MedioPago::where('activo', true)->orderBy('nombre')->get();
        return view('prestamos.edit', compact('prestamo', 'categorias', 'mediosPago'));
    }

    public function update(Request $request, Prestamo $prestamo)
    {
        $validated = $this->validar($request);

        $pagadas = $prestamo->cuotas()->where('pagada', true)->count();
        if ($validated['cantidad_cuotas'] < $pagadas) {
            return back()->withInput()->with('error',
                "Ya pagaste $pagadas cuotas; la cantidad nueva no puede ser menor.");
        }

        DB::transaction(function () use ($prestamo, $validated) {
            $prestamo->update($validated);
            // Conservar las cuotas pagadas, regenerar solo las impagas
            $prestamo->cuotas()->where('pagada', false)->delete();
            $this->generarCuotas($prestamo, soloFaltantes: true);
        });

        return redirect()->route('prestamos.index')->with('success', 'Préstamo actualizado. Las cuotas impagas se recalcularon.');
    }

    public function destroy(Prestamo $prestamo)
    {
        $prestamo->delete(); // cuotas caen por cascade
        return redirect()->route('prestamos.index')->with('success', 'Préstamo eliminado.');
    }

    public function toggleCuota(Cuota $cuota)
    {
        $cuota->update([
            'pagada'     => ! $cuota->pagada,
            'fecha_pago' => $cuota->pagada ? null : Carbon::today()->toDateString(),
        ]);

        return back()->with('success', $cuota->pagada ? 'Cuota marcada como pagada.' : 'Cuota marcada como impaga.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'descripcion'         => 'required|string|max:150',
            'categoria'           => 'required|in:camion,personal',
            'monto_total'         => 'required|numeric|min:0',
            'cantidad_cuotas'     => 'required|integer|min:1|max:120',
            'valor_cuota'         => 'required|numeric|min:0',
            'dia_vencimiento'     => 'required|integer|min:1|max:31',
            'fecha_primera_cuota' => 'required|date',
            'medio_pago_id'       => 'nullable|exists:medios_pago,id',
            'observaciones'       => 'nullable|string|max:500',
        ]);
    }

    /**
     * Genera las cuotas del préstamo. Cada cuota cae el dia_vencimiento del mes
     * correspondiente (recortado a fin de mes), partiendo de fecha_primera_cuota.
     * Si $soloFaltantes es true, respeta las cuotas que ya existen (pagadas) y solo
     * crea las que faltan hasta cantidad_cuotas.
     */
    private function generarCuotas(Prestamo $prestamo, bool $soloFaltantes = false): void
    {
        $existentes = $soloFaltantes
            ? $prestamo->cuotas()->pluck('numero')->all()
            : [];

        $base = Carbon::parse($prestamo->fecha_primera_cuota);

        for ($i = 0; $i < $prestamo->cantidad_cuotas; $i++) {
            $numero = $i + 1;
            if (in_array($numero, $existentes, true)) {
                continue; // ya existe (pagada), no la tocamos
            }

            $fecha = $base->copy()->addMonthsNoOverflow($i);
            $dia = min($prestamo->dia_vencimiento, $fecha->daysInMonth);
            $fecha = $fecha->day($dia);

            Cuota::create([
                'prestamo_id' => $prestamo->id,
                'numero'      => $numero,
                'fecha_venc'  => $fecha->toDateString(),
                'monto'       => $prestamo->valor_cuota,
                'pagada'      => false,
            ]);
        }
    }
}
