<?php

namespace App\Http\Controllers;

use App\Models\Combustible;
use App\Models\Mantenimiento;
use App\Models\Cuota;
use App\Models\Equipo;
use Carbon\Carbon;

class PagoController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
        $inicioMesActual = $hoy->copy()->startOfMonth();
        $mesProximoKey = $hoy->copy()->startOfMonth()->addMonth()->format('Y-m');
        $mesActualKey = $hoy->format('Y-m');

        // --- Gastos a crédito (las cargas de contado/débito ya están pagadas) ---
        $gastos = collect();

        $combustible = Combustible::with('medioPago')
            ->whereHas('medioPago', fn ($q) => $q->where('tipo', 'credito'))
            ->whereNotNull('fecha_vencimiento')
            ->get();

        foreach ($combustible as $c) {
            $gastos->push([
                'fecha'   => $c->fecha_vencimiento,
                'origen'  => 'Combustible',
                'detalle' => $c->lugar ?: 'Carga de combustible',
                'medio'   => $c->medioPago?->nombre ?? 'Sin medio',
                'monto'   => (float) $c->total,
                'tipo'    => 'gasto',
            ]);
        }

        $mantenimiento = Mantenimiento::with('medioPago')
            ->whereHas('medioPago', fn ($q) => $q->where('tipo', 'credito'))
            ->whereNotNull('fecha_vencimiento')
            ->get();

        foreach ($mantenimiento as $m) {
            $gastos->push([
                'fecha'   => $m->fecha_vencimiento,
                'origen'  => 'Mantenimiento',
                'detalle' => Mantenimiento::$tipos[$m->tipo] ?? $m->tipo,
                'medio'   => $m->medioPago?->nombre ?? 'Sin medio',
                'monto'   => (float) $m->monto,
                'tipo'    => 'gasto',
            ]);
        }

        // --- Cuotas de préstamos impagas ---
        $cuotas = Cuota::with('prestamo.medioPago')->where('pagada', false)->get();

        $items = collect();
        foreach ($cuotas as $cuota) {
            $items->push([
                'fecha'   => $cuota->fecha_venc,
                'origen'  => 'Préstamo',
                'detalle' => $cuota->prestamo->descripcion . ' (cuota ' . $cuota->numero . '/' . $cuota->prestamo->cantidad_cuotas . ')',
                'medio'   => $cuota->prestamo->medioPago?->nombre ?? '—',
                'monto'   => (float) $cuota->monto,
                'tipo'    => 'cuota',
            ]);
        }
        $items = $items->concat($gastos);

        // --- Atrasado: obligaciones con fecha anterior a hoy y aún no pagadas ---
        // (cuotas impagas vencidas; los gastos a crédito ya cobrados se excluyen abajo)
        $atrasado = $items->filter(fn ($i) => $i['tipo'] === 'cuota' && $i['fecha']->lt($hoy))
            ->sortBy(fn ($i) => $i['fecha']->timestamp)
            ->values();
        $totalAtrasado = $atrasado->sum('monto');

        // --- Por mes, desde hoy en adelante ---
        $futuros = $items->filter(fn ($i) => $i['fecha']->gte($hoy));

        $meses = $futuros
            ->sortBy(fn ($i) => $i['fecha']->timestamp)
            ->groupBy(fn ($i) => $i['fecha']->format('Y-m'))
            ->map(function ($grupo, $key) use ($mesProximoKey, $mesActualKey) {
                $porMedio = $grupo->groupBy('medio')->map(fn ($g) => $g->sum('monto'));
                return [
                    'key'        => $key,
                    'label'      => Carbon::createFromFormat('Y-m', $key)->translatedFormat('F Y'),
                    'items'      => $grupo->sortBy(fn ($i) => $i['fecha']->timestamp)->values(),
                    'total'      => $grupo->sum('monto'),
                    'porMedio'   => $porMedio,
                    'esProximo'  => $key === $mesProximoKey,
                    'esActual'   => $key === $mesActualKey,
                ];
            })
            ->values();

        $totalProximoMes = optional($meses->firstWhere('esProximo'))['total'] ?? 0;

        // --- Lo que se le debe a los dueños de equipos alquilados ---
        // No tiene vencimiento: se le paga cuando se arregla con él, así que
        // va aparte y no dentro de un mes.
        $alquileres = Equipo::withSum(['viajes as sin_pagar' => fn ($q) => $q->alquilerSinPagar()], 'alquiler_monto')
            ->withCount(['viajes as viajes_sin_pagar' => fn ($q) => $q->alquilerSinPagar()])
            ->orderBy('nombre')
            ->get()
            ->filter(fn ($equipo) => $equipo->sin_pagar > 0)
            ->values();

        return view('pagos.index', compact('meses', 'atrasado', 'totalAtrasado', 'totalProximoMes', 'mesProximoKey', 'alquileres'));
    }
}
