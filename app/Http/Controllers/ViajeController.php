<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Cliente;
use App\Models\Destino;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ViajeController extends Controller
{
    /** Valor de los selectores de cliente y chofer que piden crear uno nuevo con el nombre escrito. */
    private const NUEVO = 'nuevo';

    /**
     * Lo mismo para el selector de destino. Ahí las opciones son nombres y no
     * ids, así que el valor centinela lleva guiones bajos para que no pueda
     * chocar con un destino que se llame igual.
     */
    private const DESTINO_NUEVO = '__nuevo__';

    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);
        $camionId = $request->get('camion_id');
        $clienteId = $request->get('cliente_id');
        $choferId = $request->get('chofer_id');

        $viajes = Viaje::with('camion', 'cliente', 'chofer')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($choferId, fn ($q) => $q->where('chofer_id', $choferId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalPeriodo = $viajes->sum('total');
        $cantidadViajes = $viajes->count();

        $cobrados   = $viajes->where('cobrado', true);
        $noCobrados = $viajes->where('cobrado', false);

        $totalCobrado      = $cobrados->sum('total');
        $totalNoCobrado    = $noCobrados->sum('total');
        $cantidadCobrados  = $cobrados->count();
        $cantidadNoCobrados = $noCobrados->count();

        $camiones = Camion::orderBy('patente')->get();
        $clientes = Cliente::orderBy('nombre')->get();
        $choferes = Chofer::orderBy('nombre')->get();

        return view('viajes.index', compact(
            'viajes', 'totalPeriodo', 'cantidadViajes', 'periodo', 'desde', 'hasta',
            'totalCobrado', 'totalNoCobrado', 'cantidadCobrados', 'cantidadNoCobrados',
            'camiones', 'camionId', 'clientes', 'clienteId', 'choferes', 'choferId'
        ));
    }

    public function create(Request $request)
    {
        // Con ?repetir=<id> el formulario arranca con los datos de ese viaje.
        // Es $viaje a propósito: el formulario ya sabe leer de ahí, y como no
        // está guardado se envía igual a store().
        $viaje = $this->viajeParaRepetir($request->integer('repetir'));

        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $clientes = $this->clientesParaFormulario($viaje);
        $choferes = $this->choferesParaFormulario($viaje);
        $destinos = $this->destinosParaFormulario($viaje);
        $productos = Viaje::productosSugeridos();

        // Casi siempre se trabaja para el mismo cliente y con el mismo chofer:
        // vienen elegidos los del último viaje. Al repetir no hacen falta,
        // porque ya vienen los del viaje que se repite.
        $ultimo = Viaje::latest('id')->first(['cliente_id', 'chofer_id']);
        $clienteSugerido = $ultimo?->cliente_id;
        $choferSugerido = $ultimo?->chofer_id;

        return view('viajes.create', compact(
            'viaje', 'camiones', 'clientes', 'clienteSugerido',
            'choferes', 'choferSugerido', 'destinos', 'productos'
        ));
    }

    /**
     * Un viaje sin guardar con los datos del que se quiere repetir.
     *
     * Se copia lo que se repite viaje a viaje —camión, cliente, chofer, qué se
     * lleva, la ruta y el precio— y no se copia lo que trae el ticket de cada
     * uno: el número de pesada, el peso y el total. La fecha es la de ahora, y
     * el viaje arranca sin cobrar.
     */
    private function viajeParaRepetir(?int $id): ?Viaje
    {
        if (! $id) {
            return null;
        }

        $original = Viaje::find($id);

        if (! $original) {
            return null;
        }

        $repetido = new Viaje($original->only([
            'camion_id', 'cliente_id', 'chofer_id', 'modo_cobro', 'producto',
            'unidad', 'precio_unitario', 'origen', 'destino', 'km_recorridos',
        ]));

        $repetido->fecha = now();
        $repetido->cobrado = false;

        return $repetido;
    }

    public function store(Request $request)
    {
        Viaje::create($this->validar($request));

        return redirect()->route('viajes.index')->with('success', 'Viaje registrado correctamente.');
    }

    public function edit(Viaje $viaje)
    {
        $camiones = Camion::where('activo', true)->orderBy('patente')->get();
        $clientes = $this->clientesParaFormulario($viaje);
        $choferes = $this->choferesParaFormulario($viaje);
        $destinos = $this->destinosParaFormulario($viaje);
        $productos = Viaje::productosSugeridos();

        return view('viajes.edit', compact('viaje', 'camiones', 'clientes', 'choferes', 'destinos', 'productos'));
    }

    public function update(Request $request, Viaje $viaje)
    {
        $viaje->update($this->validar($request));

        return redirect()->route('viajes.index')->with('success', 'Viaje actualizado correctamente.');
    }

    public function destroy(Viaje $viaje)
    {
        $viaje->delete();
        return redirect()->route('viajes.index')->with('success', 'Viaje eliminado.');
    }

    public function toggleCobrado(Request $request, Viaje $viaje)
    {
        $viaje->update(['cobrado' => ! $viaje->cobrado]);

        if ($request->wantsJson()) {
            return response()->json([
                'cobrado' => $viaje->cobrado,
                'total'   => (float) $viaje->total,
            ]);
        }

        return back()->with('success', 'Estado de cobro actualizado.');
    }

    /**
     * Viajes ya cargados con ese número de orden, para que el formulario avise
     * de un posible duplicado mientras se escribe. Con cliente elegido sólo
     * busca entre los suyos: otro cliente puede usar la misma numeración.
     * Es sólo un aviso, no impide guardar.
     */
    public function buscarPorOrden(Request $request)
    {
        $request->validate([
            'nro_orden'  => 'required|string|max:30',
            'cliente_id' => 'nullable|integer',
            'excluir'    => 'nullable|integer',
        ]);

        $viajes = Viaje::where('nro_orden', $request->input('nro_orden'))
            ->when($request->input('cliente_id'), fn ($q, $id) => $q->where('cliente_id', $id))
            ->when($request->input('excluir'), fn ($q, $id) => $q->where('id', '!=', $id))
            ->orderByDesc('fecha')
            ->limit(5)
            ->get()
            ->map(fn (Viaje $viaje) => [
                'detalle' => collect([
                    $viaje->fecha->format('d/m/Y'),
                    $viaje->ruta(),
                    $viaje->resumenCarga(),
                    '$ ' . number_format($viaje->total, 2, ',', '.'),
                ])->reject(fn ($parte) => $parte === '—')->implode(' · '),
                'url' => route('viajes.edit', $viaje),
            ]);

        return response()->json(['viajes' => $viajes]);
    }

    /**
     * Valida el viaje y resuelve el total del lado del servidor:
     *  - modo 'fijo'     -> el total es el que escribió el usuario;
     *  - modo 'cantidad' -> el total es cantidad x precio_unitario.
     *
     * La carga (producto, cantidad y unidad) se guarda en los dos modos: qué
     * llevaste y cómo lo cobrás son datos distintos. Un flete de precio cerrado
     * igual movió 27,7 toneladas y ese peso tiene que quedar registrado.
     */
    private function validar(Request $request): array
    {
        $clienteNuevo = $request->input('cliente_id') === self::NUEVO;
        $choferNuevo  = $request->input('chofer_id') === self::NUEVO;
        $destinoNuevo = $request->input('destino') === self::DESTINO_NUEVO;

        $validated = $request->validate([
            'camion_id'       => 'required|exists:camiones,id',
            'cliente_id'      => $clienteNuevo ? 'nullable' : 'nullable|exists:clientes,id',
            'cliente_nuevo'   => $clienteNuevo ? 'required|string|max:100' : 'nullable',
            'chofer_id'       => $choferNuevo ? 'nullable' : 'nullable|exists:choferes,id',
            'chofer_nuevo'    => $choferNuevo ? 'required|string|max:100' : 'nullable',
            'modo_cobro'      => 'required|in:fijo,cantidad',
            'fecha'           => 'required|date',
            'fecha_carga'     => 'nullable|date',
            'nro_orden'       => 'nullable|string|max:30',
            'producto'        => 'nullable|string|max:60',
            'cantidad'        => 'required_if:modo_cobro,cantidad|nullable|numeric|min:0.01',
            'unidad'          => 'required_if:modo_cobro,cantidad|required_with:cantidad|nullable|string|max:20',
            'precio_unitario' => 'required_if:modo_cobro,cantidad|nullable|numeric|min:0',
            'total'           => 'required_if:modo_cobro,fijo|nullable|numeric|min:0',
            'cobrado'         => 'boolean',
            'origen'          => 'nullable|string|max:100',
            'destino'         => 'nullable|string|max:100',
            'destino_nuevo'   => $destinoNuevo ? 'required|string|max:100' : 'nullable',
            'km_recorridos'   => 'nullable|integer|min:0',
            'observaciones'   => 'nullable|string|max:500',
        ]);

        if ($validated['modo_cobro'] === 'fijo') {
            // El precio por unidad sí depende del modo: con monto fijo no existe.
            $validated['precio_unitario'] = null;
        } else {
            $validated['total'] = round($validated['cantidad'] * $validated['precio_unitario'], 2);
        }

        // Sin cantidad no hay unidad que guardar.
        if (empty($validated['cantidad'])) {
            $validated['cantidad'] = null;
            $validated['unidad']   = null;
        }

        $validated['cobrado'] = $request->boolean('cobrado');

        // Alta de cliente y de chofer desde el mismo formulario. Si ya existía uno
        // con ese nombre se usa ése, para no duplicarlo por haberlo escrito de nuevo.
        if ($clienteNuevo) {
            $validated['cliente_id'] = Cliente::firstOrCreate(['nombre' => $validated['cliente_nuevo']])->id;
        }

        if ($choferNuevo) {
            $validated['chofer_id'] = Chofer::firstOrCreate(['nombre' => $validated['chofer_nuevo']])->id;
        }

        // El destino nuevo se guarda además en el catálogo, con el origen y los
        // km de este viaje, para que el próximo los complete solo. Va después
        // del cliente porque queda asociado a él.
        if ($destinoNuevo) {
            $validated['destino'] = trim($validated['destino_nuevo']);

            Destino::firstOrCreate(
                ['cliente_id' => $validated['cliente_id'] ?? null, 'nombre' => $validated['destino']],
                [
                    'km'     => $validated['km_recorridos'] ?? null,
                    'origen' => $validated['origen'] ?? null,
                    'activo' => true,
                ]
            );
        }

        unset($validated['cliente_nuevo'], $validated['chofer_nuevo'], $validated['destino_nuevo']);

        return $validated;
    }

    /** Los clientes activos, más el del viaje que se edita aunque ya no lo esté. */
    private function clientesParaFormulario(?Viaje $viaje = null)
    {
        return Cliente::where('activo', true)
            ->when($viaje?->cliente_id, fn ($q, $id) => $q->orWhere('id', $id))
            ->orderBy('nombre')
            ->get();
    }

    /** Los choferes activos, más el del viaje que se edita aunque ya no lo esté. */
    private function choferesParaFormulario(?Viaje $viaje = null)
    {
        return Chofer::where('activo', true)
            ->when($viaje?->chofer_id, fn ($q, $id) => $q->orWhere('id', $id))
            ->orderBy('nombre')
            ->get();
    }

    /** Los destinos activos, más el del viaje que se edita aunque ya no lo esté. */
    private function destinosParaFormulario(?Viaje $viaje = null)
    {
        return Destino::with('cliente')
            ->where('activo', true)
            ->when($viaje?->destino, fn ($q, $nombre) => $q->orWhere('nombre', $nombre))
            ->orderBy('nombre')
            ->get();
    }

    private function rangoFechas(string $periodo, Request $request): array
    {
        $hoy = Carbon::today();

        return match ($periodo) {
            'hoy'      => [$hoy->toDateString(), $hoy->toDateString()],
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
