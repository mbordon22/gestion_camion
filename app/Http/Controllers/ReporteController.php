<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'mes');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);

        $viajes = Viaje::whereBetween('fecha', [$desde, $hasta])->orderByDesc('fecha')->get();
        $combustible = Combustible::whereBetween('fecha', [$desde, $hasta])->get();
        $mantenimiento = Mantenimiento::whereBetween('fecha', [$desde, $hasta])->get();

        $totalIngresos     = $viajes->sum('total');
        $totalCombustible  = $combustible->sum('total');
        $totalMantenimiento = $mantenimiento->sum('monto');
        $totalGastos       = $totalCombustible + $totalMantenimiento;
        $resultado         = $totalIngresos - $totalGastos;
        $cantidadViajes    = $viajes->count();
        $litrosCargados    = $combustible->sum('litros');

        return view('reportes.index', compact(
            'viajes', 'periodo', 'desde', 'hasta',
            'totalIngresos', 'totalCombustible', 'totalMantenimiento',
            'totalGastos', 'resultado', 'cantidadViajes', 'litrosCargados'
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
                $request->get('desde', $hoy->startOfMonth()->toDateString()),
                $request->get('hasta', $hoy->toDateString()),
            ],
            default    => [$hoy->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
        };
    }
}
