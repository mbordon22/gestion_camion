{{-- El puntito del radio: peer-checked sólo llega a hermanos y este span va anidado. --}}
<style>
    .modo-cobro:checked + span .modo-dot {
        border-color: #2563eb;
        border-width: 5px;
    }
</style>

@php
    $modoActual   = old('modo_cobro', $viaje->modo_cobro ?? 'cantidad');
    $unidadActual = old('unidad', $viaje->unidad ?? 'bolsas');
    $unidadEsOtra = $unidadActual && ! array_key_exists($unidadActual, \App\Models\Viaje::$unidades);
    $clienteActual  = old('cliente_id', isset($viaje) ? $viaje->cliente_id : ($clienteSugerido ?? null));
    $clienteEsNuevo = $clienteActual === 'nuevo';
    $choferActual   = old('chofer_id', isset($viaje) ? $viaje->chofer_id : ($choferSugerido ?? null));
    $choferEsNuevo  = $choferActual === 'nuevo';
    $destinoActual  = old('destino', $viaje->destino ?? '');
    $destinoEsNuevo = $destinoActual === '__nuevo__';
    // Un viaje viejo puede tener un destino que no está en el catálogo: se
    // ofrece igual como opción para no cambiárselo sin querer al guardar.
    $destinoSuelto  = $destinoActual !== '' && ! $destinoEsNuevo
        && ! $destinos->contains('nombre', $destinoActual);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $viaje->camion_id ?? null])

    {{-- "nuevo" crea el cliente al guardar, con el nombre escrito abajo (ViajeController::CLIENTE_NUEVO). --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
        <select name="cliente_id" id="cliente_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('cliente_id') border-red-400 @enderror">
            <option value="">Sin cliente</option>
            @foreach($clientes as $opcion)
                <option value="{{ $opcion->id }}" {{ (string) $clienteActual === (string) $opcion->id ? 'selected' : '' }}>{{ $opcion->nombre }}</option>
            @endforeach
            <option value="nuevo" {{ $clienteEsNuevo ? 'selected' : '' }}>+ Nuevo cliente…</option>
        </select>
        <input type="text" name="cliente_nuevo" id="cliente_nuevo" maxlength="100" placeholder="Nombre del cliente nuevo"
               value="{{ old('cliente_nuevo') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                      {{ $clienteEsNuevo ? '' : 'hidden' }} @error('cliente_nuevo') border-red-400 @enderror">
        @error('cliente_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('cliente_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Quien maneja. Mismo criterio que el cliente: se puede crear al vuelo. --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Chofer</label>
        <select name="chofer_id" id="chofer_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('chofer_id') border-red-400 @enderror">
            <option value="">Sin chofer</option>
            @foreach($choferes as $opcion)
                <option value="{{ $opcion->id }}" {{ (string) $choferActual === (string) $opcion->id ? 'selected' : '' }}>{{ $opcion->nombre }}</option>
            @endforeach
            <option value="nuevo" {{ $choferEsNuevo ? 'selected' : '' }}>+ Nuevo chofer…</option>
        </select>
        <input type="text" name="chofer_nuevo" id="chofer_nuevo" maxlength="100" placeholder="Nombre del chofer nuevo"
               value="{{ old('chofer_nuevo') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                      {{ $choferEsNuevo ? '' : 'hidden' }} @error('chofer_nuevo') border-red-400 @enderror">
        @error('chofer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('chofer_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Que se lleva. Texto libre con sugerencias de lo ya cargado. --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
        <input type="text" name="producto" id="producto" list="productos-sugeridos" maxlength="60" placeholder="Vinaza" autocomplete="off"
               value="{{ old('producto', $viaje->producto ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('producto') border-red-400 @enderror">
        <datalist id="productos-sugeridos">
            @foreach($productos as $sugerencia)
                <option value="{{ $sugerencia }}"></option>
            @endforeach
        </datalist>
        <p class="text-xs text-gray-400 mt-1">Qué llevás en el viaje.</p>
        @error('producto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">N° de orden</label>
        <input type="text" name="nro_orden" id="nro_orden" maxlength="30" placeholder="Ej: 10110" autocomplete="off"
               value="{{ old('nro_orden', $viaje->nro_orden ?? '') }}"
               data-url="{{ route('viajes.buscar-orden') }}"
               data-excluir="{{ $viaje->id ?? '' }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nro_orden') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Opcional, si te dieron una orden de carga o remito.</p>
        @error('nro_orden') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        <div id="orden-repetida" role="status" aria-live="polite"
             class="hidden mt-2 rounded border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900"></div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del viaje <span class="text-red-500">*</span></label>
        <input type="datetime-local" name="fecha" id="fecha"
               value="{{ old('fecha', isset($viaje) ? $viaje->fecha->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha') border-red-400 @enderror">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de carga</label>
        <input type="date" name="fecha_carga"
               value="{{ old('fecha_carga', isset($viaje) && $viaje->fecha_carga ? $viaje->fecha_carga->format('Y-m-d') : '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha_carga') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Opcional, si la carga fue otro día.</p>
        @error('fecha_carga') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Forma de cobro: define qué campos se piden abajo --}}
    <div class="sm:col-span-2">
        <span class="block text-sm font-medium text-gray-700 mb-2">¿Cómo cobrás este viaje? <span class="text-red-500">*</span></span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach(\App\Models\Viaje::$modosCobro as $valor => $etiqueta)
                <label class="block cursor-pointer">
                    <input type="radio" name="modo_cobro" value="{{ $valor }}" class="modo-cobro peer sr-only"
                           {{ $modoActual === $valor ? 'checked' : '' }}>
                    <span class="flex items-start gap-3 border-2 border-gray-200 rounded-lg p-4 transition
                                 hover:border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                        <span class="modo-dot mt-0.5 w-4 h-4 flex-shrink-0 rounded-full border-2 border-gray-300 bg-white"></span>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">{{ $etiqueta }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                {{ $valor === 'fijo' ? 'Un precio cerrado por el flete.' : 'Bolsas, toneladas, pallets, cabezas...' }}
                            </span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('modo_cobro') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{--
        La carga se registra siempre: con monto fijo es opcional, pero el peso
        del ticket igual queda guardado. Lo que sólo existe en el modo "por
        cantidad" es el precio por unidad.
    --}}
    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad <span class="text-red-500 req-carga {{ $modoActual === 'fijo' ? 'hidden' : '' }}">*</span></label>
            <input type="number" name="cantidad" id="cantidad" min="0" step="0.01" placeholder="800"
                   value="{{ old('cantidad', isset($viaje) && $viaje->cantidad ? rtrim(rtrim(number_format((float) $viaje->cantidad, 2, '.', ''), '0'), '.') : '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('cantidad') border-red-400 @enderror">
            <p id="hint-toneladas" class="text-xs text-gray-400 mt-1 {{ $unidadActual === 'toneladas' ? '' : 'hidden' }}">
                El ticket de balanza viene en kg: 27.700 kg = 27,7 t.
            </p>
            @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad <span class="text-red-500 req-carga {{ $modoActual === 'fijo' ? 'hidden' : '' }}">*</span></label>
            <select id="unidad_opcion"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                           @error('unidad') border-red-400 @enderror">
                @foreach(\App\Models\Viaje::$unidades as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ ! $unidadEsOtra && $unidadActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
                <option value="__otra__" {{ $unidadEsOtra ? 'selected' : '' }}>Otra...</option>
            </select>
            <input type="text" name="unidad" id="unidad" maxlength="20" placeholder="Ej: metros cúbicos"
                   value="{{ $unidadActual }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                          {{ $unidadEsOtra ? '' : 'hidden' }}">
            @error('unidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div id="bloque-precio" class="{{ $modoActual === 'fijo' ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio por unidad ($) <span class="text-red-500">*</span></label>
            <input type="number" name="precio_unitario" id="precio_unitario" min="0" step="0.01" placeholder="280.00"
                   value="{{ old('precio_unitario', isset($viaje) && $viaje->precio_unitario ? $viaje->precio_unitario : '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('precio_unitario') border-red-400 @enderror">
            <p id="aviso-tarifa" role="status" aria-live="polite" class="hidden text-xs mt-1"
               data-url="{{ route('tarifas.sugerir') }}"></p>
            @error('precio_unitario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Total del viaje ($) <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="number" name="total" id="total" min="0" step="0.01" placeholder="150000.00"
                   value="{{ old('total', $viaje->total ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-green-50 font-semibold text-green-800
                          focus:outline-none focus:ring-2 focus:ring-green-400">
            <span id="total-hint" class="absolute right-3 top-2 text-xs text-gray-400 {{ $modoActual === 'fijo' ? 'hidden' : '' }}">auto-calculado</span>
        </div>
        @error('total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- El destino sale del catálogo y trae consigo el origen y los km. --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
        <select name="destino" id="destino"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('destino') border-red-400 @enderror">
            <option value="">Sin destino</option>
            @foreach($destinos as $opcion)
                <option value="{{ $opcion->nombre }}"
                        data-km="{{ $opcion->km }}"
                        data-origen="{{ $opcion->origen }}"
                        {{ $destinoActual === $opcion->nombre ? 'selected' : '' }}>{{ $opcion->etiqueta() }}</option>
            @endforeach
            @if($destinoSuelto)
                <option value="{{ $destinoActual }}" selected>{{ $destinoActual }}</option>
            @endif
            <option value="__nuevo__" {{ $destinoEsNuevo ? 'selected' : '' }}>+ Nuevo destino…</option>
        </select>
        <input type="text" name="destino_nuevo" id="destino_nuevo" maxlength="100" placeholder="Nombre del destino nuevo"
               value="{{ old('destino_nuevo') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                      {{ $destinoEsNuevo ? '' : 'hidden' }} @error('destino_nuevo') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Al elegirlo se completan el origen y los km. El nuevo queda guardado con los que pongas acá abajo.</p>
        @error('destino') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        @error('destino_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
        <input type="text" name="origen" id="origen" placeholder="Ingenio La Corona"
               value="{{ old('origen', $viaje->origen ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('origen') border-red-400 @enderror">
        @error('origen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Km recorridos</label>
        <input type="number" name="km_recorridos" id="km_recorridos" min="0" placeholder="12"
               value="{{ old('km_recorridos', $viaje->km_recorridos ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('km_recorridos') border-red-400 @enderror">
        @error('km_recorridos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
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

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3" placeholder="Notas del viaje..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $viaje->observaciones ?? '') }}</textarea>
        @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<script>
(function () {
    const bloquePrecio   = document.getElementById('bloque-precio');
    const inpCantidad    = document.getElementById('cantidad');
    const inpPrecio      = document.getElementById('precio_unitario');
    const inpTotal       = document.getElementById('total');
    const hintTotal      = document.getElementById('total-hint');
    const hintToneladas  = document.getElementById('hint-toneladas');
    const selUnidad      = document.getElementById('unidad_opcion');
    const inpUnidad      = document.getElementById('unidad');
    const marcasCarga    = document.querySelectorAll('.req-carga');

    const esPorCantidad = () =>
        (document.querySelector('input[name="modo_cobro"]:checked')?.value || 'cantidad') === 'cantidad';

    // El total se calcula solo cuando se cobra por cantidad; con monto fijo
    // lo escribe el usuario. (El servidor lo recalcula igual al guardar.)
    function calcularTotal() {
        // Con monto fijo la cantidad es sólo un dato del viaje: el total lo
        // escribió el usuario y no se pisa.
        if (! esPorCantidad()) return;

        // Sin datos todavía, dejamos el campo vacío en vez de mostrar 0,00.
        if (inpCantidad.value === '' && inpPrecio.value === '') {
            inpTotal.value = '';
            return;
        }
        const cantidad = parseFloat(inpCantidad.value) || 0;
        const precio   = parseFloat(inpPrecio.value) || 0;
        inpTotal.value = (cantidad * precio).toFixed(2);
    }

    function aplicarModo() {
        const porCantidad = esPorCantidad();

        // La cantidad se sigue pidiendo con monto fijo, pero ahí es opcional.
        bloquePrecio.classList.toggle('hidden', ! porCantidad);
        marcasCarga.forEach(marca => marca.classList.toggle('hidden', ! porCantidad));
        hintTotal.classList.toggle('hidden', ! porCantidad);
        inpTotal.readOnly = porCantidad;
        inpTotal.classList.toggle('cursor-not-allowed', porCantidad);

        if (porCantidad) calcularTotal();
    }

    function aplicarUnidad() {
        const esOtra = selUnidad.value === '__otra__';
        inpUnidad.classList.toggle('hidden', ! esOtra);

        if (esOtra) {
            inpUnidad.focus();
        } else {
            inpUnidad.value = selUnidad.value;
        }

        // El ticket de balanza viene en kilos y la tarifa es por tonelada.
        hintToneladas.classList.toggle('hidden', inpUnidad.value !== 'toneladas');
    }

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

    // Origen y km son datos del destino, no de este viaje: se completan al
    // elegirlo. Nunca al abrir el formulario, para no pisar lo que ya tiene
    // cargado un viaje viejo.
    const inpOrigen = document.getElementById('origen');
    const inpKm     = document.getElementById('km_recorridos');

    function completarDesdeDestino() {
        const opcion = selDestino.selectedOptions[0];
        if (! opcion) return;

        if (opcion.dataset.km) inpKm.value = opcion.dataset.km;
        if (opcion.dataset.origen) inpOrigen.value = opcion.dataset.origen;
    }

    const selCliente = conectarNuevo('cliente_id', 'cliente_nuevo', () => {
        consultarOrden();
        consultarTarifa(true);
    });
    conectarNuevo('chofer_id', 'chofer_nuevo');
    const selDestino = conectarNuevo('destino', 'destino_nuevo', () => {
        completarDesdeDestino();
        // El destino acaba de cambiar los km, así que la tarifa puede ser otra.
        consultarTarifa(true);
    }, '__nuevo__');

    // Tarifa: el precio por unidad sale del cliente, el producto y los km.
    // Se propone, no se impone: el usuario puede cobrar otra cosa y el aviso
    // se lo recuerda. Al abrir el formulario sólo informa, no completa nada,
    // para no cambiarle el precio a un viaje ya cargado.
    const avisoTarifa = document.getElementById('aviso-tarifa');
    const inpProducto = document.getElementById('producto');
    const inpFecha    = document.getElementById('fecha');
    let tarifaActual  = null;
    let esperaTarifa;
    let ultimaTarifa  = 0;

    function mostrarAvisoTarifa() {
        if (! tarifaActual) {
            avisoTarifa.classList.add('hidden');
            return;
        }

        const precio   = parseFloat(inpPrecio.value);
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

        // Con monto fijo no hay precio por unidad que proponer.
        if (! esPorCantidad()) {
            tarifaActual = null;
            avisoTarifa.classList.add('hidden');
            return;
        }

        const url = new URL(avisoTarifa.dataset.url);
        if (selCliente.value && selCliente.value !== 'nuevo') url.searchParams.set('cliente_id', selCliente.value);
        if (inpProducto.value.trim()) url.searchParams.set('producto', inpProducto.value.trim());
        if (inpKm.value) url.searchParams.set('km', inpKm.value);
        if (inpFecha.value) url.searchParams.set('fecha', inpFecha.value);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { tarifa: null })
            .then(datos => {
                // Si se siguió tocando el formulario, esta respuesta ya no vale.
                if (consulta !== ultimaTarifa) return;

                tarifaActual = datos.tarifa;

                if (completar && tarifaActual) {
                    inpPrecio.value = tarifaActual.importe;
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

    // Aviso de orden repetida: se consulta mientras se escribe, para no
    // enterarse recién al guardar. No bloquea, porque la orden puede repetirse.
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
    inpPrecio.addEventListener('input', () => {
        calcularTotal();
        // Escribió un precio a mano: el aviso pasa a marcar la diferencia.
        mostrarAvisoTarifa();
    });
    selUnidad.addEventListener('change', () => {
        aplicarUnidad();
        mostrarAvisoTarifa();
    });
    inpProducto.addEventListener('input', consultarTarifaDemorada);
    inpKm.addEventListener('input', consultarTarifaDemorada);
    inpFecha.addEventListener('change', consultarTarifaDemorada);
    inpOrden.addEventListener('input', () => {
        clearTimeout(esperaOrden);
        esperaOrden = setTimeout(consultarOrden, 400);
    });
    inpOrden.addEventListener('change', consultarOrden);

    aplicarModo();
    consultarTarifa(false);
    if (inpOrden.value.trim() !== '') consultarOrden();
})();
</script>
