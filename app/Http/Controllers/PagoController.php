<?php

namespace App\Http\Controllers;

use App\Models\Combustible;
use App\Models\Mantenimiento;
use App\Models\Equipo;
use App\Models\Chofer;
use Carbon\Carbon;

class PagoController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();
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
            ]);
        }

        // --- Por mes, desde hoy en adelante ---
        // Lo que venció antes de hoy ya se cobró con el resumen de la tarjeta.
        $futuros = $gastos->filter(fn ($i) => $i['fecha']->gte($hoy));

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

        // --- Lo que se le debe a cada chofer a comisión ---
        // Igual que el alquiler: se le paga cuando se liquida, sin vencimiento.
        $choferes = Chofer::withSum(['viajes as comisiones' => fn ($q) => $q->comisionSinLiquidar()], 'comision_monto')
            ->withCount(['viajes as viajes_sin_liquidar' => fn ($q) => $q->comisionSinLiquidar()])
            ->withSum(['movimientos as adelantos' => fn ($q) => $q->sinLiquidar()->adelantos()], 'monto')
            ->withSum(['movimientos as gastos' => fn ($q) => $q->sinLiquidar()->gastos()], 'monto')
            ->orderBy('nombre')
            ->get()
            ->each(fn ($chofer) => $chofer->saldo = $chofer->comisiones - $chofer->adelantos + $chofer->gastos)
            ->filter(fn ($chofer) => $chofer->comisiones > 0 || $chofer->adelantos > 0 || $chofer->gastos > 0)
            ->values();

        return view('pagos.index', compact('meses', 'totalProximoMes', 'mesProximoKey', 'alquileres', 'choferes'));
    }
}
