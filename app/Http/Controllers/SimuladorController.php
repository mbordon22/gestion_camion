<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Cliente;
use App\Models\Combustible;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Producto;
use App\Models\Viaje;
use App\Support\Numero;
use App\Support\SimulacionViaje;
use Illuminate\Http\Request;

/**
 * "¿Me conviene este viaje?": se cargan los km, lo que se cobra y los gastos,
 * y se ve cuánto queda y a cuánto habría que cotizarlo. No guarda nada; si
 * conviene, "Cargar como viaje" lleva todo al formulario de viaje.
 *
 * Los datos van en la URL (GET), así una simulación se puede recargar o
 * pasar por WhatsApp tal cual.
 */
class SimuladorController extends Controller
{
    public function index(Request $request)
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $valores = $this->valores($request, $camiones);

        return view('simulador.index', [
            'valores'     => $valores,
            'simulacion'  => $this->simulacion($valores),
            'camiones'    => $camiones,
            'clientes'    => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productos'   => Producto::paraFormulario(),
            'destinos'    => Destino::with('cliente')->where('activo', true)->orderBy('nombre')->get(),
            'equipos'     => Equipo::where('activo', true)->orderBy('nombre')->get(),
            'choferes'    => Chofer::where('activo', true)->orderBy('nombre')->get(),
            'ultimoLitro' => $this->ultimaCarga($camiones->firstWhere('id', $valores['camion_id'])),
        ]);
    }

    /** Sólo el resultado, para que la pantalla lo actualice mientras se escribe. */
    public function resultado(Request $request)
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $valores = $this->valores($request, $camiones);

        return view('simulador._resultado', [
            'valores'    => $valores,
            'simulacion' => $this->simulacion($valores),
        ]);
    }

    /**
     * Lo que muestra el formulario. La primera vez (sin nada en la URL) se
     * propone como el último viaje: mismo cliente, producto, forma de cobro,
     * equipo y chofer. El consumo sale del camión y el precio del litro, de
     * la última carga de combustible.
     */
    private function valores(Request $request, $camiones): array
    {
        $enviado = $request->has('km');
        $ultimo = $enviado ? null : Viaje::latest('id')->first();

        $camion = $camiones->firstWhere('id', (int) $request->input('camion_id')) ?? $camiones->first();
        $producto = $request->input('producto', $ultimo?->producto);

        return [
            'camion_id'       => $camion?->id,
            'cliente_id'      => $request->input('cliente_id', $ultimo?->cliente_id),
            'destino'         => $request->input('destino', ''),
            'km'              => $request->input('km', ''),
            'vuelve_vacio'    => $request->input('vuelta', '1') === '1',
            'modo'            => in_array($request->input('modo'), ['cantidad', 'fijo'], true)
                ? $request->input('modo')
                : ($ultimo?->modo_cobro ?? 'cantidad'),
            'producto'        => $producto,
            'cantidad'        => $request->input('cantidad', ''),
            'unidad'          => $request->input('unidad')
                ?: (Producto::where('nombre', $producto)->value('unidad') ?? $ultimo?->unidad ?? 'toneladas'),
            'precio_unitario' => $request->input('precio_unitario', ''),
            'total'           => $request->input('total', ''),
            'consumo'         => $request->input('consumo', $camion?->consumo_cada_100km !== null ? Numero::texto($camion->consumo_cada_100km) : ''),
            'precio_litro'    => $request->input('precio_litro', Numero::texto($this->ultimaCarga($camion)?->precio_litro)),
            'equipo_id'       => $request->input('equipo_id', $ultimo?->equipo_id),
            'chofer_id'       => $request->input('chofer_id', $ultimo?->chofer_id),
            'peajes'          => $request->input('peajes', ''),
            'viaticos'        => $request->input('viaticos', ''),
            'otros'           => $request->input('otros', ''),
            'costo_km'        => $request->input('costo_km', ''),
        ];
    }

    private function simulacion(array $valores): SimulacionViaje
    {
        return new SimulacionViaje(
            km: max(0, Numero::aFloat($valores['km']) ?? 0),
            vuelveVacio: $valores['vuelve_vacio'],
            modo: $valores['modo'],
            cantidad: Numero::aFloat($valores['cantidad']),
            unidad: $valores['unidad'],
            precioUnitario: Numero::aFloat($valores['precio_unitario']),
            totalFijo: Numero::aFloat($valores['total']),
            consumo: Numero::aFloat($valores['consumo']),
            precioLitro: Numero::aFloat($valores['precio_litro']),
            equipo: $valores['equipo_id'] ? Equipo::find($valores['equipo_id']) : null,
            chofer: $valores['chofer_id'] ? Chofer::find($valores['chofer_id']) : null,
            peajes: max(0, Numero::aFloat($valores['peajes']) ?? 0),
            viaticos: max(0, Numero::aFloat($valores['viaticos']) ?? 0),
            otros: max(0, Numero::aFloat($valores['otros']) ?? 0),
            costoPorKm: max(0, Numero::aFloat($valores['costo_km']) ?? 0),
        );
    }

    /** La última carga de combustible del camión (o de cualquiera, si ése no tiene). */
    private function ultimaCarga(?Camion $camion): ?Combustible
    {
        $ultima = fn ($query) => $query->orderByDesc('fecha')->orderByDesc('id')->first();

        return ($camion ? $ultima(Combustible::where('camion_id', $camion->id)) : null)
            ?? $ultima(Combustible::query());
    }
}
