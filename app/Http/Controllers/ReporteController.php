<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use App\Models\Camion;
use App\Models\ChoferMovimiento;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);
        $camionId = $request->get('camion_id');

        $viajes = Viaje::with('cliente', 'equipo', 'chofer')->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->orderByDesc('fecha')->get();
        $combustible = Combustible::whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->get();
        $mantenimiento = Mantenimiento::whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->get();
        $gastosChofer = ChoferMovimiento::gastos()->whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->get();

        $totalIngresos     = $viajes->sum('total');
        $totalCombustible  = $combustible->sum('total');
        $totalMantenimiento = $mantenimiento->sum('monto');
        // La parte del dueño del equipo alquilado es un costo del viaje: va
        // por la fecha del viaje, se le haya pagado o no.
        $totalAlquiler     = $viajes->sum('alquiler_monto');
        // Lo mismo la comisión del chofer. Sus gastos van por la fecha del
        // gasto; los adelantos no cuentan, son parte de la comisión.
        $totalComision     = $viajes->sum('comision_monto');
        $totalGastosChofer = $gastosChofer->sum('monto');
        $totalChofer       = $totalComision + $totalGastosChofer;
        $totalGastos       = $totalCombustible + $totalMantenimiento + $totalAlquiler + $totalChofer;
        $resultado         = $totalIngresos - $totalGastos;
        $cantidadViajes    = $viajes->count();
        $litrosCargados    = $combustible->sum('litros');

        // Los gastos del camión no son de ningún cliente, así que por cliente
        // se muestran sólo los ingresos y lo que falta cobrar.
        $porCliente = $viajes->groupBy('cliente_id')
            ->map(fn ($grupo) => (object) [
                'cliente'   => $grupo->first()->cliente,
                'viajes'    => $grupo->count(),
                'total'     => $grupo->sum('total'),
                'cobrado'   => $grupo->where('cobrado', true)->sum('total'),
                'sinCobrar' => $grupo->where('cobrado', false)->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();

        // Cuánto generó cada equipo alquilado y cuánto se llevó su dueño.
        $porEquipo = $viajes->filter(fn ($viaje) => $viaje->alquiler_monto > 0)
            ->groupBy('equipo_id')
            ->map(fn ($grupo) => (object) [
                'equipo'   => $grupo->first()->equipo,
                'viajes'   => $grupo->count(),
                'bruto'    => $grupo->sum('total'),
                'alquiler' => $grupo->sum('alquiler_monto'),
                'sinPagar' => $grupo->whereNull('alquiler_pagado_el')->sum('alquiler_monto'),
            ])
            ->sortByDesc('bruto')
            ->values();

        // Cuánto generó cada chofer a comisión y cuánto se llevó.
        $porChofer = $viajes->filter(fn ($viaje) => $viaje->comision_monto > 0)
            ->groupBy('chofer_id')
            ->map(fn ($grupo, $choferId) => (object) [
                'chofer'       => $grupo->first()->chofer,
                'viajes'       => $grupo->count(),
                'bruto'        => $grupo->sum('total'),
                'comision'     => $grupo->sum('comision_monto'),
                'gastos'       => $gastosChofer->where('chofer_id', $choferId)->sum('monto'),
                'sinLiquidar'  => $grupo->whereNull('liquidacion_id')->sum('comision_monto'),
            ])
            ->sortByDesc('bruto')
            ->values();

        $camiones = Camion::orderBy('patente')->get();

        return view('reportes.index', compact(
            'viajes', 'periodo', 'desde', 'hasta',
            'totalIngresos', 'totalCombustible', 'totalMantenimiento', 'totalAlquiler', 'porEquipo',
            'totalComision', 'totalGastosChofer', 'totalChofer', 'porChofer',
            'totalGastos', 'resultado', 'cantidadViajes', 'litrosCargados',
            'camiones', 'camionId', 'porCliente'
        ));
    }

    private function rangoFechas(string $periodo, Request $request): array
    {
        $hoy = Carbon::today();

        return match ($periodo) {
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
