<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Combustible;
use App\Models\Mantenimiento;
use App\Models\Camion;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Pantalla de entrada al sistema: responde "¿cómo venimos este mes?" y deja
 * a mano las dos cargas más frecuentes (viaje y combustible).
 *
 * Es la misma cuenta que hace ReporteController, acotada al mes en curso.
 */
class InicioController extends Controller
{
    public function index(Request $request)
    {
        $hoy   = Carbon::today();
        $desde = $hoy->copy()->startOfMonth()->toDateString();
        $hasta = $hoy->copy()->endOfMonth()->toDateString();

        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $camionId = $request->get('camion_id');

        $viajes = Viaje::with('camion')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalCombustible = Combustible::whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->sum('total');

        $totalMantenimiento = Mantenimiento::whereBetween('fecha', [$desde, $hasta])
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->sum('monto');

        $totalIngresos  = $viajes->sum('total');
        $totalGastos    = $totalCombustible + $totalMantenimiento;
        $resultado      = $totalIngresos - $totalGastos;
        $cantidadViajes = $viajes->count();
        $totalPorCobrar = $viajes->where('cobrado', false)->sum('total');

        $ultimosViajes = $viajes->take(5);
        $mes = $hoy->translatedFormat('F Y');

        return view('inicio', compact(
            'resultado', 'totalIngresos', 'totalGastos', 'totalCombustible',
            'totalMantenimiento', 'cantidadViajes', 'totalPorCobrar',
            'ultimosViajes', 'mes', 'camiones', 'camionId'
        ));
    }
}
