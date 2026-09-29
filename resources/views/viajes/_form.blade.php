@php
    use App\Models\Viaje;

    // Al cargar un viaje nuevo se cobra como la última vez a ese cliente
    // (ViajeController::cobroPorCliente); si todavía no hay viajes, un precio cerrado.
    $cobroSugerido   = $cobroSugerido ?? null;
    $cobroPorCliente = $cobroPorCliente ?? collect();
    $esNuevo         = ! (isset($viaje) && $viaje->exists);

    $modoActual     = old('modo_cobro', $viaje->modo_cobro ?? ($cobroSugerido['modo'] ?? 'fijo'));
    $unidadActual   = old('unidad', $viaje->unidad ?? ($cobroSugerido['unidad'] ?? null) ?? 'toneladas');
    $unidadEsOtra   = $unidadActual && ! array_key_exists($unidadActual, Viaje::$unidades);
    $productoActual = (string) old('producto', isset($viaje) ? $viaje->producto : ($cobroSugerido['producto'] ?? ''));
    $productoEsNuevo = $productoActual === '__nuevo__';
    // Un viaje viejo puede tener un producto que ya no está en el catálogo:
    // se ofrece igual para no cambiárselo al guardar. Si sólo era la
    // sugerencia del último viaje, no hace falta.
    if ($productoActual !== '' && ! $productoEsNuevo && ! $productos->contains('nombre', $productoActual)
        && ! (isset($viaje) || old('producto'))) {
        $productoActual = '';
    }
    $productoSuelto = $productoActual !== '' && ! $productoEsNuevo && ! $productos->contains('nombre', $productoActual);
    $cantidadActual = old('cantidad', isset($viaje) ? Viaje::valorCampo($viaje->cantidad) : '');
    $precioActual   = old('precio_unitario', isset($viaje) ? Viaje::valorCampo($viaje->precio_unitario) : '');
    $totalActual    = old('total', isset($viaje) ? Viaje::valorCampo($viaje->total) : '');

    // Una sola fecha, el día del viaje. La hora es opcional y va en "Más datos".
    $fechaActual = substr(old('fecha', isset($viaje) && $viaje->fecha ? $viaje->fecha->format('Y-m-d') : date('Y-m-d')), 0, 10);
    $horaActual  = old('hora', isset($viaje) && $viaje->fecha && $viaje->fecha->format('H:i') !== '00:00' ? $viaje->fecha->format('H:i') : '');

    $clienteActual  = old('cliente_id', isset($viaje) ? $viaje->cliente_id : ($clienteSugerido ?? null));
    $clienteEsNuevo = $clienteActual === 'nuevo';
    $choferActual   = old('chofer_id', isset($viaje) ? $viaje->chofer_id : ($choferSugerido ?? null));
    $choferEsNuevo  = $choferActual === 'nuevo';
    $equipoActual   = old('equipo_id', isset($viaje) ? $viaje->equipo_id : ($equipoSugerido ?? null));
    $destinoActual  = old('destino', $viaje->destino ?? '');
    $destinoEsNuevo = $destinoActual === '__nuevo__';
    // Un viaje viejo puede tener un destino que no está en el catálogo: se
    // ofrece igual como opción para no cambiárselo sin querer al guardar.
    $destinoSuelto  = $destinoActual !== '' && ! $destinoEsNuevo
        && ! $destinos->contains('nombre', $destinoActual);

    // Lo que no se usa no estorba: el chofer queda a la vista sólo si ya hay
    // choferes cargados. El N° de orden y la tarifa, si la cuenta los usa
    // (Configuración); si no, no aparecen.
    $choferALaVista = $choferes->isNotEmpty();
    $usaTarifas     = \App\Support\CuentaActual::usa('tarifas');

    // La carga (qué y cuánto) se pide siempre al cobrar por cantidad; con
    // precio cerrado es opcional y aparece si ya tiene algo.
    $mostrarCarga = $modoActual === 'cantidad'
        || filled($productoActual) || filled($cantidadActual)
        || $errors->hasAny(['producto', 'producto_nuevo', 'cantidad', 'unidad']);

    $abrirRuta = $destinoEsNuevo || $errors->hasAny(['origen', 'km_recorridos']);

    $abrirMas = filled($horaActual)
        || filled(old('fecha_carga', isset($viaje) ? $viaje->fecha_carga : null))
        || filled(old('observaciones', $viaje->observaciones ?? null))
        || (! $choferALaVista && $choferEsNuevo)
        || $errors->hasAny(['hora', 'fecha_carga', 'observaciones', 'nro_orden', 'chofer_id', 'chofer_nuevo']);

    $claseCampo = 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400';
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $viaje->camion_id ?? null])

    <div>
        <label for="fecha" class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
        <div class="flex gap-2">
            <input type="date" name="fecha" id="fecha" value="{{ $fechaActual }}"
                   class="{{ $claseCampo }} min-w-0 @error('fecha') border-red-400 @enderror">
            <button type="button" data-dias-atras="0"
                    class="fecha-rapida px-3 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-100 transition">Hoy</button>
            <button type="button" data-dias-atras="1"
                    class="fecha-rapida px-3 rounded border border-gray-300 text-sm text-gray-700 hover:bg-gray-100 transition">Ayer</button>
        </div>
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- "nuevo" crea el cliente al guardar, con el nombre escrito abajo (ViajeController::NUEVO). --}}
    <div>
        <label for="cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
        <select name="cliente_id" id="cliente_id"
                data-cobro="{{ $esNuevo ? $cobroPorCliente->toJson() : '{}' }}"
                class="{{ $claseCampo }} @error('cliente_id') border-red-400 @enderror">
            <option value="">Sin cliente</option>
            @foreach($clientes as $opcion)
                <option value="{{ $opcion->id }}" {{ (string) $clienteActual === (string) $opcion->id ? 'selected' : '' }}>{{ $opcion->nombre }}</option>
            @endforeach
            <option value="nuevo" {{ $clienteEsNuevo ? 'selected' : '' }}>+ Nuevo cliente…</option>
        </select>
        <input type="text" name="cliente_nuevo" id="cliente_nuevo" maxlength="100" placeholder="Nombre del cliente nuevo"
               value="{{ old('cliente_nuevo') }}"
               class="{{ $claseCampo }} mt-2 {{ $clienteEsNuevo ? '' : 'hidden' }} @error('cliente_nuevo') border-red-400 @enderror">
        @error('cliente_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('cliente_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @if($usaOrden)
        @include('viajes._nro_orden')
    @endif

    @if($choferALaVista)
        @include('viajes._chofer')
    @endif

    {{--
        Lo que va enganchado: si es alquilado, el dueño se lleva una parte del
        total. Cada opción lleva su acuerdo para que el aviso de abajo haga la
        cuenta. En un viaje ya cargado con este mismo equipo vale lo que se
        grabó entonces, no el acuerdo de hoy (Equipo::alquilerDe).
    --}}
    @if($equipos->isNotEmpty())
        <div>
            <label for="equipo_id" class="block text-sm font-medium text-gray-700 mb-1">Equipo</label>
            <select name="equipo_id" id="equipo_id"
                    class="{{ $claseCampo }} @error('equipo_id') border-red-400 @enderror">
                <option value="">Sin equipo</option>
                @foreach($equipos as $opcion)
                    @php
                        $grabado = isset($viaje) && $viaje->exists && $viaje->equipo_id === $opcion->id;
                        $valorAcuerdo = $opcion->esPorcentaje()
                            ? ($grabado && $viaje->alquiler_porcentaje !== null ? $viaje->alquiler_porcentaje : $opcion->valor)
                            : ($grabado && $viaje->alquiler_monto !== null ? $viaje->alquiler_monto : $opcion->valor);
                    @endphp
                    <option value="{{ $opcion->id }}"
                            data-camion="{{ $opcion->camion_id }}"
                            data-alquilado="{{ $opcion->alquilado ? 1 : 0 }}"
                            data-modalidad="{{ $opcion->modalidad }}"
                            data-valor="{{ $valorAcuerdo }}"
                            {{ (string) $equipoActual === (string) $opcion->id ? 'selected' : '' }}>
                        {{ $opcion->etiqueta() }}{{ $opcion->alquilado ? ' — alquilado' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 mt-1">Acoplado, semi, cisterna… Si es alquilado, se descuenta la parte del dueño.</p>
            @error('equipo_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    @endif

    {{--
        El destino sale del catálogo y trae consigo el origen y los km, que se
        muestran resumidos. Los campos se abren para un destino nuevo o al
        tocar "Cambiar". Sin JavaScript quedan siempre abiertos.
    --}}
    <div class="sm:col-span-2">
        <label for="destino" class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
        <select name="destino" id="destino"
                class="{{ $claseCampo }} @error('destino') border-red-400 @enderror">
            <option value="">Sin destino</option>
            @foreach($destinos as $opcion)
                <option value="{{ $opcion->nombre }}"
                        data-km="{{ $opcion->km }}"
                        data-origen="{{ $opcion->origen }}"
                        {{ $destinoActual === $opcion->nombre ? 'selected' : '' }}>{{ $opcion->etiqueta() }}</option>
            @endforeach
            @if($destinoSuelto)
                {{-- Con su origen y km, para que volver a elegirlo los recupere. --}}
                <option value="{{ $destinoActual }}"
                        data-km="{{ old('km_recorridos', $viaje->km_recorridos ?? '') }}"
                        data-origen="{{ old('origen', $viaje->origen ?? '') }}"
                        selected>{{ $destinoActual }}</option>
            @endif
            <option value="__nuevo__" {{ $destinoEsNuevo ? 'selected' : '' }}>+ Nuevo destino…</option>
        </select>
        <input type="text" name="destino_nuevo" id="destino_nuevo" maxlength="100" placeholder="Nombre del destino nuevo"
               value="{{ old('destino_nuevo') }}"
               class="{{ $claseCampo }} mt-2 {{ $destinoEsNuevo ? '' : 'hidden' }} @error('destino_nuevo') border-red-400 @enderror">
        @error('destino') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('destino_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

        <p id="ruta-resumen" class="hidden text-xs text-gray-500 mt-1">
            <span id="ruta-texto"></span>
            <button type="button" id="ruta-cambiar" class="ml-1 text-blue-600 hover:text-blue-800 font-medium underline">Cambiar</button>
        </p>

        <div id="ruta-campos" data-abierta="{{ $abrirRuta ? 1 : 0 }}" class="grid grid-cols-2 gap-3 mt-3">
            <div>
                <label for="origen" class="block text-xs font-medium text-gray-600 mb-1">Origen</label>
                <input type="text" name="origen" id="origen" placeholder="Ej: planta, campo, puerto"
                       value="{{ old('origen', $viaje->origen ?? '') }}"
                       class="{{ $claseCampo }} @error('origen') border-red-400 @enderror">
                @error('origen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="km_recorridos" class="block text-xs font-medium text-gray-600 mb-1">Km recorridos</label>
                <input type="number" name="km_recorridos" id="km_recorridos" min="0" inputmode="numeric" placeholder="Ej: 120"
                       value="{{ old('km_recorridos', $viaje->km_recorridos ?? '') }}"
                       class="{{ $claseCampo }} @error('km_recorridos') border-red-400 @enderror">
                @error('km_recorridos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <p id="ruta-ayuda" class="col-span-2 text-xs text-gray-400 {{ $destinoEsNuevo ? '' : 'hidden' }}">
                Quedan guardados con el destino nuevo: la próxima vez se completan solos.
            </p>
        </div>
    </div>

    {{--
        El cobro. Lo más común es un precio cerrado por el flete: se escribe el
        total y listo. "Por cantidad" pide la carga y el precio por unidad, y
        el total sale solo (el servidor lo recalcula igual al guardar).
    --}}
    <div class="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm font-medium text-gray-700">¿Cómo cobrás este viaje? <span class="text-red-500">*</span></span>
            <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 gap-1" role="radiogroup" aria-label="Forma de cobro">
                @foreach(Viaje::$modosCobro as $valor => $etiqueta)
                    <label class="cursor-pointer">
                        <input type="radio" name="modo_cobro" value="{{ $valor }}" class="modo-cobro peer sr-only"
                               {{ $modoActual === $valor ? 'checked' : '' }}>
                        <span class="block px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 transition
                                     hover:bg-gray-100 peer-checked:bg-blue-600 peer-checked:text-white
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $etiqueta }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @error('modo_cobro') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

        <button type="button" id="anotar-carga"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium {{ $mostrarCarga ? 'hidden' : '' }}">
            + Anotar qué llevaste (producto, peso, bolsas…)
        </button>

        <div id="bloque-carga" class="grid grid-cols-2 sm:grid-cols-3 gap-3 {{ $mostrarCarga ? '' : 'hidden' }}">
            {{--
                Qué se lleva, del catálogo de productos. Cada opción lleva su
                unidad habitual, que se completa al elegirlo. "+ Nuevo producto…"
                lo crea al guardar, como el destino.
            --}}
            <div class="col-span-2 sm:col-span-1">
                <label for="producto" class="block text-xs font-medium text-gray-600 mb-1">Producto</label>
                <select name="producto" id="producto"
                        class="{{ $claseCampo }} bg-white @error('producto') border-red-400 @enderror">
                    <option value="">Sin producto</option>
                    @foreach($productos as $opcion)
                        <option value="{{ $opcion->nombre }}" data-unidad="{{ $opcion->unidad }}"
                                {{ $productoActual === $opcion->nombre ? 'selected' : '' }}>{{ $opcion->nombre }}</option>
                    @endforeach
                    @if($productoSuelto)
                        <option value="{{ $productoActual }}" selected>{{ $productoActual }}</option>
                    @endif
                    <option value="__nuevo__" {{ $productoEsNuevo ? 'selected' : '' }}>+ Nuevo producto…</option>
                </select>
                <input type="text" name="producto_nuevo" id="producto_nuevo" maxlength="60" placeholder="Ej: cereal, hacienda"
                       value="{{ old('producto_nuevo') }}"
                       class="{{ $claseCampo }} bg-white mt-2 {{ $productoEsNuevo ? '' : 'hidden' }} @error('producto_nuevo') border-red-400 @enderror">
                @error('producto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                @error('producto_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="cantidad" class="block text-xs font-medium text-gray-600 mb-1">Cantidad <span class="text-red-500 req-carga {{ $modoActual === 'fijo' ? 'hidden' : '' }}">*</span></label>
                <input type="text" name="cantidad" id="cantidad" inputmode="decimal" autocomplete="off" placeholder="Ej: 28,5"
                       value="{{ $cantidadActual }}"
                       class="{{ $claseCampo }} bg-white @error('cantidad') border-red-400 @enderror">
                <p id="hint-toneladas" class="text-xs text-gray-400 mt-1 {{ $unidadActual === 'toneladas' ? '' : 'hidden' }}">
                    El ticket de balanza viene en kg: 27.700 kg = 27,7 t.
                </p>
                @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="unidad_opcion" class="block text-xs font-medium text-gray-600 mb-1">Unidad <span class="text-red-500 req-carga {{ $modoActual === 'fijo' ? 'hidden' : '' }}">*</span></label>
                <select id="unidad_opcion"
                        class="{{ $claseCampo }} bg-white @error('unidad') border-red-400 @enderror">
                    @foreach(Viaje::$unidades as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ ! $unidadEsOtra && $unidadActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                    @endforeach
                    <option value="__otra__" {{ $unidadEsOtra ? 'selected' : '' }}>Otra...</option>
                </select>
                <input type="text" name="unidad" id="unidad" maxlength="20" placeholder="Ej: metros cúbicos"
                       value="{{ $unidadActual }}"
                       class="{{ $claseCampo }} bg-white mt-2 {{ $unidadEsOtra ? '' : 'hidden' }}">
                @error('unidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div id="bloque-precio" class="{{ $modoActual === 'fijo' ? 'hidden' : '' }}">
            <label for="precio_unitario" class="block text-xs font-medium text-gray-600 mb-1">Precio por unidad ($) <span class="text-red-500">*</span></label>
            <input type="text" name="precio_unitario" id="precio_unitario" inputmode="decimal" autocomplete="off" placeholder="Ej: 8.500"
                   value="{{ $precioActual }}"
                   class="{{ $claseCampo }} bg-white @error('precio_unitario') border-red-400 @enderror">
            <p id="aviso-tarifa" role="status" aria-live="polite" class="hidden text-xs mt-1"
               data-url="{{ $usaTarifas ? route('tarifas.sugerir') : '' }}"></p>
            @error('precio_unitario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="total" class="block text-sm font-medium text-gray-700 mb-1">Total del viaje ($) <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="text" name="total" id="total" inputmode="decimal" autocomplete="off" placeholder="Ej: 150.000"
                       value="{{ $totalActual }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-base bg-green-50 font-semibold text-green-800
                              focus:outline-none focus:ring-2 focus:ring-green-400 @error('total') border-red-400 @enderror">
                <span id="total-hint" class="absolute right-3 top-2.5 text-xs text-gray-400 {{ $modoActual === 'fijo' ? 'hidden' : '' }}">se calcula solo</span>
            </div>
            @error('total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <p id="aviso-alquiler" role="status" aria-live="polite" class="hidden text-xs mt-1 text-gray-600"></p>
            @if(isset($viaje) && $viaje->alquiler_pagado_el)
                <p class="text-xs mt-1 text-green-700">Al dueño del equipo ya se le pagó este viaje el {{ $viaje->alquiler_pagado_el->format('d/m/Y') }}.</p>
            @endif
            @if(isset($viaje) && $viaje->exists && $viaje->liquidacion_id)
                <p class="text-xs mt-1 text-green-700">
                    Al chofer ya se le liquidó este viaje el {{ $viaje->liquidacion?->fecha->format('d/m/Y') }}: su comisión queda como se pagó.
                    Si cambiás de chofer, sale de esa liquidación.
                </p>
            @endif
        </div>

        <label class="inline-flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="cobrado" value="0">
            <input type="checkbox" name="cobrado" value="1" class="sr-only peer"
                   {{ old('cobrado', $viaje->cobrado ?? false) ? 'checked' : '' }}>
            <span class="relative w-11 h-6 bg-gray-300 rounded-full transition-colors
                         peer-checked:bg-green-500
                         after:content-[''] after:absolute after:top-0.5 after:left-0.5
                         after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                         peer-checked:after:translate-x-5"></span>
            <span class="text-sm font-medium text-gray-700">Ya lo cobré</span>
        </label>
    </div>

    {{-- Lo que no hace falta en cada viaje. Viene abierto si ya tiene algo cargado. --}}
    <details class="sm:col-span-2 border-t border-gray-200 pt-4" {{ $abrirMas ? 'open' : '' }}>
        <summary class="cursor-pointer select-none text-sm font-medium text-blue-700 hover:text-blue-900">
            Más datos
            <span class="font-normal text-gray-500">
                ({{ collect([
                    $choferALaVista ? null : 'chofer',
                    'hora',
                    'fecha de carga',
                    'observaciones',
                ])->filter()->implode(', ') }})
            </span>
        </summary>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-4">
            @unless($choferALaVista)
                @include('viajes._chofer')
            @endunless

            <div>
                <label for="hora" class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                <input type="time" name="hora" id="hora" value="{{ $horaActual }}"
                       class="{{ $claseCampo }} @error('hora') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">La de la orden de carga, si la querés anotar.</p>
                @error('hora') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="fecha_carga" class="block text-sm font-medium text-gray-700 mb-1">Fecha de carga</label>
                <input type="date" name="fecha_carga" id="fecha_carga"
                       value="{{ old('fecha_carga', isset($viaje) && $viaje->fecha_carga ? $viaje->fecha_carga->format('Y-m-d') : '') }}"
                       class="{{ $claseCampo }} @error('fecha_carga') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Si cargaste otro día.</p>
                @error('fecha_carga') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observaciones" id="observaciones" rows="3" placeholder="Notas del viaje..."
                          class="{{ $claseCampo }} @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $viaje->observaciones ?? '') }}</textarea>
                @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </details>
</div>

<script>
(function () {
    const bloquePrecio   = document.getElementById('bloque-precio');
    const bloqueCarga    = document.getElementById('bloque-carga');
    const btnAnotar      = document.getElementById('anotar-carga');
    const inpCantidad    = document.getElementById('cantidad');
    const inpPrecio      = document.getElementById('precio_unitario');
    const inpTotal       = document.getElementById('total');
    const hintTotal      = document.getElementById('total-hint');
    const hintToneladas  = document.getElementById('hint-toneladas');
    const selUnidad      = document.getElementById('unidad_opcion');
    const inpUnidad      = document.getElementById('unidad');
    const inpProducto    = document.getElementById('producto');
    const inpProductoNuevo = document.getElementById('producto_nuevo');
    const inpFecha       = document.getElementById('fecha');
    const marcasCarga    = document.querySelectorAll('.req-carga');

    const esPorCantidad = () =>
        (document.querySelector('input[name="modo_cobro"]:checked')?.value || 'fijo') === 'cantidad';

    // Los montos se escriben como en cualquier lado: "150.000", "27,7".
    // Misma regla que ViajeController::numero, que es el que vale al guardar.
    function leerNumero(texto) {
        let t = String(texto).replace(/[\s$]/g, '');
        if (t === '') return NaN;
        if (t.includes(',')) {
            t = t.replace(/\./g, '').replace(',', '.');
        } else if (/^\d{1,3}(\.\d{3})+$/.test(t)) {
            t = t.replace(/\./g, '');
        }
        return Number(t);
    }

    const enCampo = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 });
    const pesos   = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // --- Fecha: Hoy / Ayer -------------------------------------------------

    const botonesFecha = document.querySelectorAll('.fecha-rapida');

    function diaLocal(diasAtras) {
        const d = new Date();
        d.setDate(d.getDate() - diasAtras);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function marcarFechaRapida() {
        botonesFecha.forEach(boton => {
            const activo = inpFecha.value === diaLocal(Number(boton.dataset.diasAtras));
            boton.classList.toggle('bg-blue-50', activo);
            boton.classList.toggle('border-blue-400', activo);
            boton.classList.toggle('text-blue-700', activo);
        });
    }

    botonesFecha.forEach(boton => boton.addEventListener('click', () => {
        inpFecha.value = diaLocal(Number(boton.dataset.diasAtras));
        marcarFechaRapida();
        consultarTarifaDemorada();
    }));

    // --- Cobro -------------------------------------------------------------

    // El total se calcula solo cuando se cobra por cantidad; con precio
    // cerrado lo escribe el usuario. (El servidor lo recalcula igual al guardar.)
    function calcularTotal() {
        // Con precio cerrado la cantidad es sólo un dato del viaje: el total
        // lo escribió el usuario y no se pisa.
        if (! esPorCantidad()) return;

        // Mientras falte la cantidad o el precio, el campo queda vacío en vez
        // de mostrar 0 (la tarifa suele completar el precio antes que el peso).
        if (inpCantidad.value.trim() === '' || inpPrecio.value.trim() === '') {
            inpTotal.value = '';
            mostrarAlquiler();
            return;
        }
        const cantidad = leerNumero(inpCantidad.value) || 0;
        const precio   = leerNumero(inpPrecio.value) || 0;
        inpTotal.value = enCampo.format(Math.round(cantidad * precio * 100) / 100);
        mostrarAlquiler();
    }

    function mostrarCarga() {
        bloqueCarga.classList.remove('hidden');
        btnAnotar.classList.add('hidden');
    }

    // Con precio cerrado y nada anotado, la carga vuelve a quedar guardada.
    function ocultarCargaSiVacia() {
        if (esPorCantidad() || inpProducto.value.trim() || inpCantidad.value.trim()) return;
        bloqueCarga.classList.add('hidden');
        btnAnotar.classList.remove('hidden');
    }

    function aplicarModo() {
        const porCantidad = esPorCantidad();

        // La carga se sigue pidiendo con precio cerrado, pero ahí es opcional
        // y no se esconde si ya está a la vista.
        if (porCantidad) mostrarCarga();
        bloquePrecio.classList.toggle('hidden', ! porCantidad);
        marcasCarga.forEach(marca => marca.classList.toggle('hidden', ! porCantidad));
        hintTotal.classList.toggle('hidden', ! porCantidad);
        inpTotal.readOnly = porCantidad;
        inpTotal.classList.toggle('cursor-not-allowed', porCantidad);

        if (porCantidad) calcularTotal();
    }

    function aplicarUnidad(enfocar = true) {
        const esOtra = selUnidad.value === '__otra__';
        inpUnidad.classList.toggle('hidden', ! esOtra);

        if (esOtra) {
            if (enfocar) inpUnidad.focus();
        } else {
            inpUnidad.value = selUnidad.value;
        }

        // El ticket de balanza viene en kilos y la tarifa es por tonelada.
        hintToneladas.classList.toggle('hidden', inpUnidad.value !== 'toneladas');
    }

    function elegirUnidad(unidad) {
        const conocida = [...selUnidad.options].some(o => o.value === unidad);
        selUnidad.value = conocida ? unidad : '__otra__';
        inpUnidad.value = unidad;
        aplicarUnidad(false);
    }

    btnAnotar.addEventListener('click', () => {
        mostrarCarga();
        inpProducto.focus();
    });

    // Cliente, chofer y destino se pueden crear desde acá: al elegir
    // "+ Nuevo..." aparece el campo del nombre. El estado inicial lo deja
    // puesto Blade.
    function conectarNuevo(idSelect, idInput, alCambiar, valorNuevo = 'nuevo') {
        const select = document.getElementById(idSelect);
        const input  = document.getElementById(idInput);

        select.addEventListener('change', () => {
            const esNuevo = select.value === valorNuevo;
            input.classList.toggle('hidden', ! esNuevo);
            if (esNuevo) input.focus();
            if (alCambiar) alCambiar();
        });

        return select;
    }

    // --- Recorrido ---------------------------------------------------------

    // Origen y km son datos del destino, no de este viaje: al elegir otro se
    // reemplazan por los suyos, y si no los tiene (o es "Sin destino") quedan
    // vacíos, para no guardar ocultos los del destino anterior. Nunca al
    // abrir el formulario, para no pisar lo que ya tiene cargado un viaje viejo.
    const inpOrigen    = document.getElementById('origen');
    const inpKm        = document.getElementById('km_recorridos');
    const rutaCampos   = document.getElementById('ruta-campos');
    const rutaResumen  = document.getElementById('ruta-resumen');
    const rutaTexto    = document.getElementById('ruta-texto');
    const rutaCambiar  = document.getElementById('ruta-cambiar');
    const rutaAyuda    = document.getElementById('ruta-ayuda');
    let rutaAbierta    = rutaCampos.dataset.abierta === '1';

    function completarDesdeDestino() {
        const opcion = selDestino.selectedOptions[0];

        // Uno nuevo se escribe con los campos a la vista: lo que haya queda
        // como punto de partida (suele salir del mismo lugar).
        if (! opcion || opcion.value === '__nuevo__') return;

        inpKm.value     = opcion.dataset.km || '';
        inpOrigen.value = opcion.dataset.origen || '';
    }

    // Con un destino del catálogo alcanza con ver "Desde X · 120 km"; los
    // campos se abren para un destino nuevo o al tocar "Cambiar".
    function actualizarRuta() {
        const esNuevo = selDestino.value === '__nuevo__';
        const abrir = rutaAbierta || esNuevo;

        rutaCampos.classList.toggle('hidden', ! abrir);
        rutaAyuda.classList.toggle('hidden', ! esNuevo);

        const partes = [];
        if (inpOrigen.value.trim()) partes.push('Desde ' + inpOrigen.value.trim());
        if (inpKm.value) partes.push(inpKm.value + ' km');

        if (abrir || (partes.length === 0 && ! selDestino.value)) {
            rutaResumen.classList.add('hidden');
            return;
        }

        rutaTexto.textContent = partes.length ? partes.join(' · ') : 'Sin origen ni km.';
        rutaCambiar.textContent = partes.length ? 'Cambiar' : 'Agregar';
        rutaResumen.classList.remove('hidden');
    }

    rutaCambiar.addEventListener('click', () => {
        rutaAbierta = true;
        actualizarRuta();
        inpOrigen.focus();
    });

    // --- Lo que se lleva el dueño del equipo y el chofer ---------------------

    // Equipo alquilado y chofer a comisión: cuánto se lleva cada uno y
    // cuánto queda para el camión. Es sólo el aviso; los montos los calcula
    // el servidor al guardar.
    const selEquipo     = document.getElementById('equipo_id');
    const selChofer     = document.getElementById('chofer_id');
    const avisoAlquiler = document.getElementById('aviso-alquiler');
    const selCamion     = document.getElementById('camion_id');

    // Lo que se lleva alguien según su acuerdo: [monto, " (15%)"].
    function parteDe(opcion, total) {
        const valor = parseFloat(opcion.dataset.valor) || 0;
        const porcentaje = opcion.dataset.modalidad === 'porcentaje';
        return [
            porcentaje ? Math.round(total * valor) / 100 : valor,
            porcentaje ? ' (' + String(valor).replace('.', ',') + '%)' : '',
        ];
    }

    function mostrarAlquiler() {
        const total  = leerNumero(inpTotal.value);
        const equipo = selEquipo?.selectedOptions[0];
        const chofer = selChofer.selectedOptions[0];
        const partes = [];
        let queda = total;

        if (! isNaN(total) && equipo && equipo.dataset.alquilado === '1') {
            const [monto, detalle] = parteDe(equipo, total);
            partes.push('el dueño del equipo $ ' + pesos.format(monto) + detalle);
            queda -= monto;
        }
        if (! isNaN(total) && chofer && chofer.dataset.modalidad) {
            const [monto, detalle] = parteDe(chofer, total);
            partes.push('el chofer $ ' + pesos.format(monto) + detalle);
            queda -= monto;
        }

        if (partes.length === 0) {
            avisoAlquiler.classList.add('hidden');
            return;
        }

        const texto = partes.join(' y ');
        avisoAlquiler.textContent = 'Se lleva' + (partes.length > 1 ? 'n ' : ' ') + texto
            + '. Te quedan $ ' + pesos.format(queda) + '.';
        avisoAlquiler.classList.remove('hidden');
    }

    // El equipo va casi siempre con el mismo camión: si se cambia de camión
    // y el equipo elegido es de otro, se propone el del camión nuevo.
    function equipoDelCamion() {
        if (! selEquipo || ! selCamion) return;

        const actual = selEquipo.selectedOptions[0];
        if (actual && actual.dataset.camion && actual.dataset.camion !== selCamion.value) {
            const delCamion = [...selEquipo.options].find(o => o.dataset.camion === selCamion.value);
            selEquipo.value = delCamion ? delCamion.value : '';
        }
        mostrarAlquiler();
    }

    // --- Cada cliente, como se le cobró la última vez ------------------------

    // Sólo al cargar un viaje nuevo: a uno se le cobra por tonelada, a otro un
    // precio cerrado. El producto se propone si no hay ninguno elegido o si lo
    // había puesto esto mismo, para no pisar lo que eligió el usuario.
    const selClienteBase  = document.getElementById('cliente_id');
    const cobroPorCliente = JSON.parse(selClienteBase.dataset.cobro || '{}');
    let productoPropuesto = inpProducto.value;

    function aplicarCobroDelCliente() {
        const cobro = cobroPorCliente[selClienteBase.value];
        if (! cobro) return;

        const radio = document.querySelector('.modo-cobro[value="' + cobro.modo + '"]');
        if (radio) radio.checked = true;
        if (cobro.unidad) elegirUnidad(cobro.unidad);

        if (inpProducto.value === '' || inpProducto.value === productoPropuesto) {
            // Si ya no está en el catálogo, no se propone.
            const enCatalogo = [...inpProducto.options].some(o => o.value === cobro.producto);
            inpProducto.value = enCatalogo ? cobro.producto : '';
            productoPropuesto = inpProducto.value;
            if (inpProducto.value) mostrarCarga();
        }

        aplicarModo();
        ocultarCargaSiVacia();
    }

    const selCliente = conectarNuevo('cliente_id', 'cliente_nuevo', () => {
        aplicarCobroDelCliente();
        consultarOrden();
        consultarTarifa(true);
    });
    conectarNuevo('chofer_id', 'chofer_nuevo', mostrarAlquiler);
    const selDestino = conectarNuevo('destino', 'destino_nuevo', () => {
        completarDesdeDestino();
        actualizarRuta();
        // El destino acaba de cambiar los km, así que la tarifa puede ser otra.
        consultarTarifa(true);
    }, '__nuevo__');

    // --- Tarifa ------------------------------------------------------------

    // El precio por unidad sale del cliente, el producto y los km. Se
    // propone, no se impone: el usuario puede cobrar otra cosa y el aviso se
    // lo recuerda. Al abrir el formulario sólo informa, no completa nada,
    // para no cambiarle el precio a un viaje ya cargado.
    const avisoTarifa = document.getElementById('aviso-tarifa');
    let tarifaActual  = null;
    let esperaTarifa;
    let ultimaTarifa  = 0;
    // El precio que puso la tarifa, para sacarlo si deja de corresponder.
    // Si el usuario lo cambió a mano, ya es suyo y no se toca.
    let precioPropuesto = null;

    function mostrarAvisoTarifa() {
        if (! tarifaActual) {
            avisoTarifa.classList.add('hidden');
            return;
        }

        const precio   = leerNumero(inpPrecio.value);
        const distinto = ! isNaN(precio) && Math.abs(precio - tarifaActual.importe) > 0.005;
        const unidad   = inpUnidad.value;
        const otraUnidad = unidad && unidad !== tarifaActual.unidad;

        let texto = 'Tarifa: ' + tarifaActual.detalle + '.';
        if (otraUnidad) {
            texto += ' Ojo: este viaje está cargado en ' + unidad + '.';
        } else if (distinto) {
            texto += ' Estás cobrando otro precio.';
        }

        avisoTarifa.textContent = texto;
        avisoTarifa.className = (otraUnidad || distinto)
            ? 'text-xs mt-1 text-amber-700 font-medium'
            : 'text-xs mt-1 text-gray-500';
    }

    function consultarTarifa(completar) {
        clearTimeout(esperaTarifa);
        const consulta = ++ultimaTarifa;

        // Con precio cerrado no hay precio por unidad que proponer. Sin
        // tarifas (data-url vacío), tampoco.
        if (! esPorCantidad() || ! avisoTarifa.dataset.url) {
            tarifaActual = null;
            avisoTarifa.classList.add('hidden');
            return;
        }

        const url = new URL(avisoTarifa.dataset.url);
        if (selCliente.value && selCliente.value !== 'nuevo') url.searchParams.set('cliente_id', selCliente.value);
        const producto = inpProducto.value === '__nuevo__' ? inpProductoNuevo.value.trim() : inpProducto.value;
        if (producto) url.searchParams.set('producto', producto);
        if (inpKm.value) url.searchParams.set('km', inpKm.value);
        if (inpFecha.value) url.searchParams.set('fecha', inpFecha.value);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { tarifa: null })
            .then(datos => {
                // Si se siguió tocando el formulario, esta respuesta ya no vale.
                if (consulta !== ultimaTarifa) return;

                tarifaActual = datos.tarifa;

                if (completar && tarifaActual) {
                    inpPrecio.value = enCampo.format(tarifaActual.importe);
                    precioPropuesto = inpPrecio.value;
                    calcularTotal();
                } else if (completar && precioPropuesto !== null && inpPrecio.value === precioPropuesto) {
                    // Otro destino, otros km: la tarifa de antes ya no vale.
                    inpPrecio.value = '';
                    precioPropuesto = null;
                    calcularTotal();
                }

                mostrarAvisoTarifa();
            })
            .catch(() => {}); // Es sólo una sugerencia: si falla, no molesta.
    }

    function consultarTarifaDemorada() {
        clearTimeout(esperaTarifa);
        esperaTarifa = setTimeout(() => consultarTarifa(true), 400);
    }

    // --- N° de orden repetido ----------------------------------------------

    // Se consulta mientras se escribe, para no enterarse recién al guardar.
    // No bloquea, porque la orden puede repetirse.
    const inpOrden   = document.getElementById('nro_orden');
    const avisoOrden = document.getElementById('orden-repetida');
    let esperaOrden;
    let ultimaConsulta = 0;

    function mostrarRepetidos(nro, viajes) {
        avisoOrden.replaceChildren();
        avisoOrden.classList.toggle('hidden', viajes.length === 0);
        if (viajes.length === 0) return;

        const titulo = document.createElement('p');
        titulo.className = 'font-semibold';
        titulo.textContent = viajes.length === 1
            ? `Ya cargaste un viaje con la orden ${nro}:`
            : `Ya cargaste ${viajes.length} viajes con la orden ${nro}:`;
        avisoOrden.append(titulo);

        viajes.forEach(v => {
            const ver = document.createElement('a');
            ver.href = v.url;
            ver.target = '_blank';
            ver.rel = 'noopener';
            ver.title = 'Se abre en otra pestaña';
            ver.className = 'font-medium underline';
            ver.textContent = 'Ver';

            const linea = document.createElement('p');
            linea.className = 'mt-1';
            linea.append(v.detalle + ' · ', ver);
            avisoOrden.append(linea);
        });

        const pie = document.createElement('p');
        pie.className = 'mt-1 text-amber-700';
        pie.textContent = 'Si es otro viaje, podés guardarlo igual.';
        avisoOrden.append(pie);
    }

    function consultarOrden() {
        if (! inpOrden) return; // La cuenta no usa N° de orden.
        clearTimeout(esperaOrden);
        const nro = inpOrden.value.trim();
        const consulta = ++ultimaConsulta;

        // Un cliente recién creado todavía no tiene viajes: no hay con qué repetir.
        if (nro === '' || selCliente.value === 'nuevo') {
            mostrarRepetidos(nro, []);
            return;
        }

        const url = new URL(inpOrden.dataset.url);
        url.searchParams.set('nro_orden', nro);
        if (selCliente.value) url.searchParams.set('cliente_id', selCliente.value);
        if (inpOrden.dataset.excluir) url.searchParams.set('excluir', inpOrden.dataset.excluir);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { viajes: [] })
            .then(data => {
                // Si se siguió escribiendo, esta respuesta ya no corresponde.
                if (consulta === ultimaConsulta) mostrarRepetidos(nro, data.viajes);
            })
            .catch(() => {}); // Es sólo un aviso: si falla, no molesta.
    }

    document.querySelectorAll('.modo-cobro').forEach(r => r.addEventListener('change', () => {
        aplicarModo();
        consultarTarifa(false);
    }));
    inpCantidad.addEventListener('input', calcularTotal);
    inpTotal.addEventListener('input', mostrarAlquiler);
    selEquipo?.addEventListener('change', mostrarAlquiler);
    selCamion?.addEventListener('change', equipoDelCamion);
    inpPrecio.addEventListener('input', () => {
        calcularTotal();
        // Escribió un precio a mano: el aviso pasa a marcar la diferencia.
        mostrarAvisoTarifa();
    });
    selUnidad.addEventListener('change', () => {
        aplicarUnidad();
        mostrarAvisoTarifa();
    });
    // Cada producto se mide casi siempre igual: al elegirlo se pone su unidad.
    conectarNuevo('producto', 'producto_nuevo', () => {
        const unidad = inpProducto.selectedOptions[0]?.dataset.unidad;
        if (unidad) elegirUnidad(unidad);
        consultarTarifa(true);
    }, '__nuevo__');
    inpProductoNuevo.addEventListener('input', consultarTarifaDemorada);
    inpKm.addEventListener('input', consultarTarifaDemorada);
    inpFecha.addEventListener('change', () => {
        marcarFechaRapida();
        consultarTarifaDemorada();
    });
    inpOrden?.addEventListener('input', () => {
        clearTimeout(esperaOrden);
        esperaOrden = setTimeout(consultarOrden, 400);
    });
    inpOrden?.addEventListener('change', consultarOrden);

    marcarFechaRapida();
    actualizarRuta();
    aplicarModo();
    mostrarAlquiler();
    consultarTarifa(false);
    if (inpOrden?.value.trim()) consultarOrden();
})();
</script>
