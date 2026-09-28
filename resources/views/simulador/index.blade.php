@extends('layouts.app')

@section('title', 'Simular viaje')

@section('content')
@php
    $campo = 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400';
    $etiqueta = 'block text-sm font-medium text-gray-700 mb-1';
    $seccion = 'bg-white rounded-lg shadow p-4 sm:p-5';
    $tituloSeccion = 'text-sm font-bold uppercase tracking-wide text-gray-500 mb-3';
    $unidades = \App\Models\Viaje::$unidades;
    $unidadEsOtra = $valores['unidad'] && ! array_key_exists($valores['unidad'], $unidades);
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Simular viaje</h1>
    <p class="text-sm text-gray-500 mt-1">Cargá el viaje y los gastos, y fijate si te rinde y a cuánto conviene cotizarlo.</p>
</div>

{{-- pb en el celular: la barra del resultado queda fija abajo. --}}
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6 pb-16 sm:pb-0">
    <form id="form-simulador" method="GET" action="{{ route('simulador.index') }}" class="lg:col-span-3 space-y-4"
          data-resultado="{{ route('simulador.resultado') }}">

        {{-- El viaje --}}
        <section class="{{ $seccion }}">
            <h2 class="{{ $tituloSeccion }}">El viaje</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if($camiones->count() > 1)
                    <div>
                        <label for="camion_id" class="{{ $etiqueta }}">Camión</label>
                        <select name="camion_id" id="camion_id" class="{{ $campo }}">
                            @foreach($camiones as $camion)
                                <option value="{{ $camion->id }}" data-consumo="{{ \App\Support\Numero::texto($camion->consumo_cada_100km) }}"
                                        {{ (string) $valores['camion_id'] === (string) $camion->id ? 'selected' : '' }}>{{ $camion->nombre() }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($camiones->isNotEmpty())
                    <input type="hidden" name="camion_id" value="{{ $camiones->first()->id }}">
                @endif

                <div>
                    <label for="cliente_id" class="{{ $etiqueta }}">Cliente</label>
                    <select name="cliente_id" id="cliente_id" class="{{ $campo }}">
                        <option value="">Sin cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" {{ (string) $valores['cliente_id'] === (string) $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="destino" class="{{ $etiqueta }}">Destino</label>
                    <select name="destino" id="destino" class="{{ $campo }}">
                        <option value="">Otro / sin destino</option>
                        @foreach($destinos as $destino)
                            <option value="{{ $destino->nombre }}" data-km="{{ $destino->km }}"
                                    {{ $valores['destino'] === $destino->nombre ? 'selected' : '' }}>{{ $destino->etiqueta() }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Si está en el catálogo, trae los km.</p>
                </div>

                <div>
                    <label for="km" class="{{ $etiqueta }}">Km del viaje (ida) <span class="text-red-500">*</span></label>
                    <input type="number" name="km" id="km" min="0" inputmode="numeric" placeholder="Ej: 50"
                           value="{{ $valores['km'] }}" class="{{ $campo }}">
                </div>

                <label class="sm:col-span-2 inline-flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                    <input type="hidden" name="vuelta" value="0">
                    <input type="checkbox" name="vuelta" value="1" id="vuelta" {{ $valores['vuelve_vacio'] ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                    <span>Vuelve vacío <span class="text-gray-500">— se suma la vuelta al gasoil y al desgaste</span></span>
                </label>
            </div>
        </section>

        {{-- Lo que cobrás --}}
        <section class="{{ $seccion }}">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="{{ $tituloSeccion }} !mb-0">Lo que cobrás</h2>
                <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 gap-1" role="radiogroup" aria-label="Forma de cobro">
                    @foreach(['cantidad' => 'Por cantidad', 'fijo' => 'Precio cerrado'] as $valor => $texto)
                        <label class="cursor-pointer">
                            <input type="radio" name="modo" value="{{ $valor }}" class="modo-sim peer sr-only" {{ $valores['modo'] === $valor ? 'checked' : '' }}>
                            <span class="block px-3 py-1.5 rounded-md text-sm font-medium text-gray-600 transition hover:bg-gray-100
                                         peer-checked:bg-blue-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400">{{ $texto }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="bloque-cantidad" class="grid grid-cols-2 sm:grid-cols-3 gap-4 {{ $valores['modo'] === 'fijo' ? 'hidden' : '' }}">
                <div class="col-span-2 sm:col-span-1">
                    <label for="producto" class="{{ $etiqueta }}">Producto</label>
                    <select name="producto" id="producto" class="{{ $campo }}">
                        <option value="">Sin producto</option>
                        @foreach($productos as $producto)
                            <option value="{{ $producto->nombre }}" data-unidad="{{ $producto->unidad }}"
                                    {{ $valores['producto'] === $producto->nombre ? 'selected' : '' }}>{{ $producto->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="cantidad" class="{{ $etiqueta }}">Cantidad</label>
                    <input type="text" name="cantidad" id="cantidad" inputmode="decimal" placeholder="Ej: 28" autocomplete="off"
                           value="{{ $valores['cantidad'] }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="unidad_opcion" class="{{ $etiqueta }}">Unidad</label>
                    <select id="unidad_opcion" class="{{ $campo }}">
                        @foreach($unidades as $valor => $texto)
                            <option value="{{ $valor }}" {{ ! $unidadEsOtra && $valores['unidad'] === $valor ? 'selected' : '' }}>{{ $texto }}</option>
                        @endforeach
                        <option value="__otra__" {{ $unidadEsOtra ? 'selected' : '' }}>Otra...</option>
                    </select>
                    <input type="text" name="unidad" id="unidad" maxlength="20" placeholder="Ej: metros cúbicos"
                           value="{{ $valores['unidad'] }}" class="{{ $campo }} mt-2 {{ $unidadEsOtra ? '' : 'hidden' }}">
                </div>
                <div class="col-span-2 sm:col-span-3">
                    <label for="precio_unitario" class="{{ $etiqueta }}">Precio por unidad ($)</label>
                    <input type="text" name="precio_unitario" id="precio_unitario" inputmode="decimal" placeholder="Ej: 9.094,25" autocomplete="off"
                           value="{{ $valores['precio_unitario'] }}" class="{{ $campo }}">
                    <p id="aviso-tarifa" class="hidden text-xs text-gray-500 mt-1" data-url="{{ route('tarifas.sugerir') }}"></p>
                </div>
            </div>

            <div id="bloque-fijo" class="{{ $valores['modo'] === 'fijo' ? '' : 'hidden' }}">
                <label for="total" class="{{ $etiqueta }}">Total del viaje ($)</label>
                <input type="text" name="total" id="total" inputmode="decimal" placeholder="Ej: 150.000" autocomplete="off"
                       value="{{ $valores['total'] }}" class="{{ $campo }}">
            </div>
        </section>

        {{-- Combustible --}}
        <section class="{{ $seccion }}">
            <h2 class="{{ $tituloSeccion }}">Combustible</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="consumo" class="{{ $etiqueta }}">Consumo (L cada 100 km)</label>
                    <input type="text" name="consumo" id="consumo" inputmode="decimal" placeholder="Ej: 35" autocomplete="off"
                           value="{{ $valores['consumo'] }}" class="{{ $campo }}">
                    <p class="text-xs text-gray-400 mt-1">
                        Guardalo en <a href="{{ route('camiones.index') }}" class="underline">Camiones</a> y viene solo.
                    </p>
                </div>
                <div>
                    <label for="precio_litro" class="{{ $etiqueta }}">Precio del litro ($)</label>
                    <input type="text" name="precio_litro" id="precio_litro" inputmode="decimal" placeholder="Ej: 1.450" autocomplete="off"
                           value="{{ $valores['precio_litro'] }}" class="{{ $campo }}">
                    @if($ultimoLitro)
                        <p class="text-xs text-gray-400 mt-1">Tu última carga: $ {{ \App\Support\Numero::texto($ultimoLitro->precio_litro) }} ({{ $ultimoLitro->fecha->format('d/m') }}).</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Equipo y chofer --}}
        @if($equipos->isNotEmpty() || $choferes->isNotEmpty())
            <section class="{{ $seccion }}">
                <h2 class="{{ $tituloSeccion }}">Equipo y chofer</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @if($equipos->isNotEmpty())
                        <div>
                            <label for="equipo_id" class="{{ $etiqueta }}">Equipo</label>
                            <select name="equipo_id" id="equipo_id" class="{{ $campo }}">
                                <option value="">Sin equipo</option>
                                @foreach($equipos as $equipo)
                                    <option value="{{ $equipo->id }}" {{ (string) $valores['equipo_id'] === (string) $equipo->id ? 'selected' : '' }}>
                                        {{ $equipo->etiqueta() }} — {{ $equipo->condicion() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($choferes->isNotEmpty())
                        <div>
                            <label for="chofer_id" class="{{ $etiqueta }}">Chofer</label>
                            <select name="chofer_id" id="chofer_id" class="{{ $campo }}">
                                <option value="">Lo manejo yo</option>
                                @foreach($choferes as $chofer)
                                    <option value="{{ $chofer->id }}" {{ (string) $valores['chofer_id'] === (string) $chofer->id ? 'selected' : '' }}>
                                        {{ $chofer->nombre }} — {{ $chofer->condicion() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- Otros gastos --}}
        <section class="{{ $seccion }}">
            <h2 class="{{ $tituloSeccion }}">Otros gastos del viaje</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="peajes" class="{{ $etiqueta }}">Peajes ($)</label>
                    <input type="text" name="peajes" id="peajes" inputmode="decimal" placeholder="0" autocomplete="off"
                           value="{{ $valores['peajes'] }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="viaticos" class="{{ $etiqueta }}">Comida y viáticos ($)</label>
                    <input type="text" name="viaticos" id="viaticos" inputmode="decimal" placeholder="0" autocomplete="off"
                           value="{{ $valores['viaticos'] }}" class="{{ $campo }}">
                </div>
                <div>
                    <label for="otros" class="{{ $etiqueta }}">Otros ($)</label>
                    <input type="text" name="otros" id="otros" inputmode="decimal" placeholder="0" autocomplete="off"
                           value="{{ $valores['otros'] }}" class="{{ $campo }}">
                    <p class="text-xs text-gray-400 mt-1">Balanza, lavado, ayudante…</p>
                </div>
                <div>
                    <label for="costo_km" class="{{ $etiqueta }}">Desgaste por km ($)</label>
                    <input type="text" name="costo_km" id="costo_km" inputmode="decimal" placeholder="0" autocomplete="off"
                           value="{{ $valores['costo_km'] }}" class="{{ $campo }}">
                    <p class="text-xs text-gray-400 mt-1">Lo que te cuestan cubiertas, service y repuestos por cada km.</p>
                </div>
            </div>
        </section>

        {{-- Sin JavaScript, el resultado se calcula con este botón. --}}
        <noscript>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded shadow">Calcular</button>
        </noscript>
    </form>

    <div id="resultado" class="lg:col-span-2 lg:sticky lg:top-4 self-start scroll-mt-4" aria-live="polite">
        @include('simulador._resultado')
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('form-simulador');
    const resultado = document.getElementById('resultado');
    const $ = (id) => document.getElementById(id);

    // --- El resultado se recalcula en el servidor mientras se escribe ------

    let espera;
    let ultimaConsulta = 0;

    function recalcular() {
        clearTimeout(espera);
        espera = setTimeout(function () {
            const consulta = ++ultimaConsulta;
            const parametros = new URLSearchParams(new FormData(form));

            // La URL queda con la simulación: se puede recargar o compartir.
            history.replaceState(null, '', form.action + '?' + parametros);

            fetch(form.dataset.resultado + '?' + parametros, { headers: { 'Accept': 'text/html' } })
                .then(r => r.ok ? r.text() : Promise.reject())
                .then(html => { if (consulta === ultimaConsulta) resultado.innerHTML = html; })
                .catch(() => {});
        }, 300);
    }

    form.addEventListener('input', recalcular);
    form.addEventListener('change', recalcular);
    form.addEventListener('submit', function (e) { e.preventDefault(); recalcular(); });

    // --- Forma de cobro ----------------------------------------------------

    function aplicarModo() {
        const porCantidad = form.querySelector('input[name="modo"]:checked')?.value !== 'fijo';
        $('bloque-cantidad').classList.toggle('hidden', ! porCantidad);
        $('bloque-fijo').classList.toggle('hidden', porCantidad);
    }
    form.querySelectorAll('.modo-sim').forEach(r => r.addEventListener('change', () => { aplicarModo(); consultarTarifa(); }));

    // --- Unidad: las de siempre o una escrita a mano ------------------------

    const selUnidad = $('unidad_opcion');
    const inpUnidad = $('unidad');

    function elegirUnidad(unidad) {
        const conocida = [...selUnidad.options].some(o => o.value === unidad);
        selUnidad.value = conocida ? unidad : '__otra__';
        inpUnidad.value = unidad;
        inpUnidad.classList.toggle('hidden', conocida);
    }

    selUnidad.addEventListener('change', function () {
        const otra = selUnidad.value === '__otra__';
        inpUnidad.classList.toggle('hidden', ! otra);
        if (otra) { inpUnidad.value = ''; inpUnidad.focus(); } else { inpUnidad.value = selUnidad.value; }
    });

    // El producto trae su unidad; el destino, sus km; el camión, su consumo.
    $('producto').addEventListener('change', function () {
        const unidad = this.selectedOptions[0]?.dataset.unidad;
        if (unidad) elegirUnidad(unidad);
        consultarTarifa();
    });

    $('destino').addEventListener('change', function () {
        const km = this.selectedOptions[0]?.dataset.km;
        if (km) $('km').value = km;
        consultarTarifa();
    });

    const selCamion = $('camion_id');
    let consumoPropuesto = $('consumo').value;
    selCamion?.addEventListener('change', function () {
        const consumo = this.selectedOptions[0]?.dataset.consumo || '';
        if ($('consumo').value === '' || $('consumo').value === consumoPropuesto) {
            $('consumo').value = consumo;
            consumoPropuesto = consumo;
        }
    });

    // --- La tarifa propone el precio por unidad ------------------------------

    // Se propone, no se impone: si el precio lo escribió el usuario, sólo avisa.
    const avisoTarifa = $('aviso-tarifa');
    let precioPropuesto = null;
    let ultimaTarifa = 0;

    function consultarTarifa() {
        const consulta = ++ultimaTarifa;
        const porCantidad = form.querySelector('input[name="modo"]:checked')?.value !== 'fijo';
        const cliente = $('cliente_id').value;
        const km = $('km').value;

        if (! porCantidad || ! cliente || ! km) {
            avisoTarifa.classList.add('hidden');
            return;
        }

        const url = new URL(avisoTarifa.dataset.url);
        url.searchParams.set('cliente_id', cliente);
        url.searchParams.set('km', km);
        if ($('producto').value) url.searchParams.set('producto', $('producto').value);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : { tarifa: null })
            .then(datos => {
                if (consulta !== ultimaTarifa) return;
                const precio = $('precio_unitario');
                if (! datos.tarifa) {
                    avisoTarifa.classList.add('hidden');
                    if (precioPropuesto !== null && precio.value === precioPropuesto) { precio.value = ''; recalcular(); }
                    precioPropuesto = null;
                    return;
                }
                const importe = new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 }).format(datos.tarifa.importe);
                if (precio.value === '' || precio.value === precioPropuesto) {
                    precio.value = importe;
                    precioPropuesto = importe;
                    recalcular();
                }
                avisoTarifa.textContent = 'Tarifa: ' + datos.tarifa.detalle + '.';
                avisoTarifa.classList.remove('hidden');
            })
            .catch(() => {});
    }

    let esperaKm;
    $('cliente_id').addEventListener('change', consultarTarifa);
    $('km').addEventListener('input', () => { clearTimeout(esperaKm); esperaKm = setTimeout(consultarTarifa, 400); });

    aplicarModo();
    consultarTarifa();
})();
</script>
@endsection
