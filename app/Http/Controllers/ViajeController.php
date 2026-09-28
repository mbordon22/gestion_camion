<?php

namespace App\Http\Controllers;

use App\Models\Viaje;
use App\Models\Camion;
use App\Models\Chofer;
use App\Models\Cliente;
use App\Models\Destino;
use App\Models\Equipo;
use App\Models\Producto;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ViajeController extends Controller
{
    /** Valor de los selectores de cliente y chofer que piden crear uno nuevo con el nombre escrito. */
    private const NUEVO = 'nuevo';

    /**
     * Lo mismo para los selectores de destino y de producto. Ahí las opciones
     * son nombres y no ids, así que el valor centinela lleva guiones bajos
     * para que no pueda chocar con uno que se llame igual.
     */
    private const NOMBRE_NUEVO = '__nuevo__';

    public function index(Request $request)
    {
        $periodo = $request->get('periodo', 'rango');
        [$desde, $hasta] = $this->rangoFechas($periodo, $request);
        $camionId = $request->get('camion_id');
        $clienteId = $request->get('cliente_id');
        $choferId = $request->get('chofer_id');

        $viajes = Viaje::with('camion', 'cliente', 'chofer', 'equipo')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($camionId, fn ($q) => $q->where('camion_id', $camionId))
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($choferId, fn ($q) => $q->where('chofer_id', $choferId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $totalPeriodo = $viajes->sum('total');
        $totalAlquiler = $viajes->sum('alquiler_monto');
        $totalComision = $viajes->sum('comision_monto');
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
            'viajes', 'totalPeriodo', 'totalAlquiler', 'totalComision', 'cantidadViajes', 'periodo', 'desde', 'hasta',
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

        // Casi siempre se trabaja para el mismo cliente, con el mismo chofer y
        // el mismo equipo enganchado: vienen elegidos los del último viaje. Al
        // repetir no hacen falta, porque ya vienen los del viaje que se repite.
        $ultimo = Viaje::with('cliente')->latest('id')->first();
        $clienteSugerido = $ultimo?->cliente_id;
        $choferSugerido = $ultimo?->chofer_id;
        $equipoSugerido = $ultimo?->equipo_id;

        // Y se cobra como la última vez que se le hizo un viaje a ese cliente.
        // Si todavía no hay viajes, lo más común: un precio cerrado.
        $cobroPorCliente = $this->cobroPorCliente();
        $cobroSugerido = $cobroPorCliente->get($clienteSugerido)
            ?? ($ultimo ? $this->cobroDe($ultimo) : null);

        return view('viajes.create', $this->datosFormulario($viaje) + compact(
            'viaje', 'ultimo', 'clienteSugerido', 'choferSugerido', 'equipoSugerido',
            'cobroPorCliente', 'cobroSugerido'
        ));
    }

    /** Lo que necesita el formulario, sea para cargar un viaje o para editarlo. */
    private function datosFormulario(?Viaje $viaje): array
    {
        return [
            'camiones'  => Camion::where('activo', true)->orderBy('patente')->get(),
            'clientes'  => $this->clientesParaFormulario($viaje),
            'choferes'  => $this->choferesParaFormulario($viaje),
            'destinos'  => $this->destinosParaFormulario($viaje),
            'equipos'   => $this->equiposParaFormulario($viaje),
            'productos' => Producto::paraFormulario($viaje?->producto),
            // El N° de orden va a la vista sólo para quien ya lo usa; si no,
            // queda en "Más datos".
            'usaOrden'  => Viaje::whereNotNull('nro_orden')->exists(),
        ];
    }

    /**
     * Cómo se cobró el último viaje de cada cliente, para que el formulario
     * lo proponga al elegirlo: a uno se le cobra por tonelada, a otro un
     * precio cerrado.
     */
    private function cobroPorCliente()
    {
        $ultimos = Viaje::selectRaw('max(id)')->whereNotNull('cliente_id')->groupBy('cliente_id');

        return Viaje::whereIn('id', $ultimos)->get()
            ->mapWithKeys(fn (Viaje $viaje) => [$viaje->cliente_id => $this->cobroDe($viaje)]);
    }

    private function cobroDe(Viaje $viaje): array
    {
        return [
            'modo'     => $viaje->modo_cobro,
            'unidad'   => $viaje->unidad,
            'producto' => $viaje->producto,
        ];
    }

    /**
     * Un viaje sin guardar con los datos del que se quiere repetir.
     *
     * Se copia lo que se repite viaje a viaje —camión, cliente, chofer, qué se
     * lleva, la ruta y el precio— y no se copia lo que trae el ticket de cada
     * uno: el número de pesada, el peso y el total que sale del peso. La
     * fecha es la de hoy, y el viaje arranca sin cobrar.
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
            'camion_id', 'cliente_id', 'chofer_id', 'equipo_id', 'modo_cobro', 'producto',
            'unidad', 'precio_unitario', 'origen', 'destino', 'km_recorridos',
        ]));

        // Con precio cerrado, el precio del flete es el total mismo.
        if ($original->esMontoFijo()) {
            $repetido->total = $original->total;
        }

        $repetido->fecha = today();
        $repetido->cobrado = false;

        return $repetido;
    }

    public function store(Request $request)
    {
        $viaje = Viaje::create($this->validar($request));

        // El listado ofrece cargar otro igual: es lo más común después de guardar.
        return redirect()->route('viajes.index')
            ->with('success', 'Viaje registrado correctamente.')
            ->with('viaje_guardado', $viaje->id);
    }

    public function edit(Viaje $viaje)
    {
        return view('viajes.edit', $this->datosFormulario($viaje) + compact('viaje'));
    }

    public function update(Request $request, Viaje $viaje)
    {
        $viaje->update($this->validar($request, $viaje));

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
     *
     * Con un equipo alquilado también resuelve cuánto se lleva el dueño, y con
     * un chofer a comisión cuánto se lleva el chofer, los dos sobre el total
     * bruto. $anterior es el viaje que se edita, para respetar lo que ya se
     * había grabado.
     */
    private function validar(Request $request, ?Viaje $anterior = null): array
    {
        $clienteNuevo = $request->input('cliente_id') === self::NUEVO;
        $choferNuevo  = $request->input('chofer_id') === self::NUEVO;
        $destinoNuevo = $request->input('destino') === self::NOMBRE_NUEVO;
        $productoNuevo = $request->input('producto') === self::NOMBRE_NUEVO;

        // Los montos llegan como los escribe cualquiera: "150.000", "27,7".
        foreach (['cantidad', 'precio_unitario', 'total'] as $campo) {
            $request->merge([$campo => $this->numero($request->input($campo))]);
        }

        $validated = $request->validate([
            'camion_id'       => 'required|exists:camiones,id',
            'cliente_id'      => $clienteNuevo ? 'nullable' : 'nullable|exists:clientes,id',
            'cliente_nuevo'   => $clienteNuevo ? 'required|string|max:100' : 'nullable',
            'chofer_id'       => $choferNuevo ? 'nullable' : 'nullable|exists:choferes,id',
            'chofer_nuevo'    => $choferNuevo ? 'required|string|max:100' : 'nullable',
            'equipo_id'       => 'nullable|exists:equipos,id',
            'modo_cobro'      => 'required|in:fijo,cantidad',
            'fecha'           => 'required|date',
            'hora'            => 'nullable|date_format:H:i',
            'fecha_carga'     => 'nullable|date',
            'nro_orden'       => 'nullable|string|max:30',
            'producto'        => 'nullable|string|max:60',
            'producto_nuevo'  => $productoNuevo ? 'required|string|max:60' : 'nullable',
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

        // El formulario pide sólo el día; la hora es opcional y va aparte.
        if (! empty($validated['hora'])) {
            $validated['fecha'] = Carbon::parse($validated['fecha'])->setTimeFromTimeString($validated['hora']);
        }

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

        $equipo = isset($validated['equipo_id']) ? Equipo::find($validated['equipo_id']) : null;

        $validated = array_merge($validated, $equipo
            ? $equipo->alquilerDe((float) $validated['total'], $anterior)
            : ['alquiler_porcentaje' => null, 'alquiler_monto' => null]);

        // Si cambió el equipo, lo que se le haya pagado al dueño anterior ya
        // no corresponde a este viaje.
        if (! $validated['alquiler_monto'] || $anterior?->equipo_id !== $equipo?->id) {
            $validated['alquiler_pagado_el'] = null;
        }

        // Alta de cliente y de chofer desde el mismo formulario. Si ya existía uno
        // con ese nombre se usa ése, para no duplicarlo por haberlo escrito de nuevo.
        if ($clienteNuevo) {
            $validated['cliente_id'] = Cliente::firstOrCreate(['nombre' => $validated['cliente_nuevo']])->id;
        }

        if ($choferNuevo) {
            $validated['chofer_id'] = Chofer::firstOrCreate(['nombre' => $validated['chofer_nuevo']])->id;
        }

        // La comisión del chofer, también sobre el bruto. Va después del alta
        // al vuelo porque necesita saber quién es el chofer.
        $chofer = isset($validated['chofer_id']) ? Chofer::find($validated['chofer_id']) : null;

        $validated = array_merge($validated, $chofer
            ? $chofer->comisionDe((float) $validated['total'], $anterior)
            : ['comision_porcentaje' => null, 'comision_monto' => null]);

        // Si cambió el chofer, el viaje sale de la liquidación del anterior.
        if ($anterior?->liquidacion_id && $anterior->chofer_id !== $chofer?->id) {
            $validated['liquidacion_id'] = null;
        }

        // El producto nuevo queda en el catálogo, con la unidad de este viaje
        // como la habitual. Si ya existía escrito distinto ("vinaza"), se usa
        // el del catálogo: la tarifa se busca por ese nombre.
        if ($productoNuevo) {
            $validated['producto'] = Producto::firstOrCreate(
                ['nombre' => trim($validated['producto_nuevo'])],
                ['unidad' => $validated['unidad'] ?? null, 'activo' => true]
            )->nombre;
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

        unset(
            $validated['cliente_nuevo'], $validated['chofer_nuevo'], $validated['destino_nuevo'],
            $validated['producto_nuevo'], $validated['hora']
        );

        return $validated;
    }

    /**
     * Un número escrito a mano, pasado a lo que entiende la validación.
     *
     * Con coma se lee en criollo: la coma es el decimal y los puntos separan
     * los miles ("1.500,50"). Sin coma, el punto es decimal ("27.7"), salvo
     * que agrupe de a tres cifras ("150.000"), que es como se escribe un
     * monto redondo. Lo que no se pueda leer se deja como vino, para que la
     * validación lo rechace.
     */
    private function numero(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $texto = str_replace(['$', ' '], '', trim($valor));

        if ($texto === '') {
            return null;
        }

        if (str_contains($texto, ',')) {
            return str_replace(['.', ','], ['', '.'], $texto);
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $texto)) {
            return str_replace('.', '', $texto);
        }

        return $texto;
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

    /** Los equipos activos, más el del viaje que se edita aunque ya no lo esté. */
    private function equiposParaFormulario(?Viaje $viaje = null)
    {
        return Equipo::where('activo', true)
            ->when($viaje?->equipo_id, fn ($q, $id) => $q->orWhere('id', $id))
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
