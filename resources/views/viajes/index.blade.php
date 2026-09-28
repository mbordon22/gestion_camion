@extends('layouts.app')

@section('title', 'Viajes')
@section('container-class', 'w-full')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Viajes</h1>
    <div class="flex flex-wrap gap-2">
        {{-- Recién guardado: lo más común es cargar otro igual. --}}
        @if(session('viaje_guardado'))
            <a href="{{ route('viajes.create', ['repetir' => session('viaje_guardado')]) }}"
               class="inline-flex items-center gap-2 bg-white hover:bg-blue-50 text-blue-700 font-medium px-4 py-2 rounded border border-blue-300 shadow-sm transition">
                Cargar otro igual
            </a>
        @endif
        <a href="{{ route('viajes.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo viaje
        </a>
    </div>
</div>

{{-- Filtros. En el celular, plegados con lo elegido a la vista. --}}
@php
    $resumenFiltros = collect([
        $clientes->firstWhere('id', $clienteId)?->nombre,
        $choferes->firstWhere('id', $choferId)?->nombre,
        $camiones->firstWhere('id', $camionId)?->nombre(),
        \Carbon\Carbon::parse($desde)->format('d/m') . ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m'),
    ])->filter()->implode(' · ');
@endphp
<x-filtros :resumen="$resumenFiltros">
    <form method="GET" action="{{ route('viajes.index') }}" class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end">
        @if($clientes->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                <select name="cliente_id" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Todos los clientes</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ (string) $clienteId === (string) $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($choferes->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Chofer</label>
                <select name="chofer_id" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Todos los choferes</option>
                    @foreach($choferes as $chofer)
                        <option value="{{ $chofer->id }}" {{ (string) $choferId === (string) $chofer->id ? 'selected' : '' }}>
                            {{ $chofer->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($camiones->count() > 1)
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Camión</label>
                <select name="camion_id" onchange="this.form.submit()"
                        class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">Todos los camiones</option>
                    @foreach($camiones as $camion)
                        <option value="{{ $camion->id }}" {{ (string) $camionId === (string) $camion->id ? 'selected' : '' }}>
                            {{ $camion->nombre() }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Período</label>
            <select name="periodo" onchange="this.form.submit()"
                    class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="hoy"    {{ $periodo === 'hoy'    ? 'selected' : '' }}>Hoy</option>
                <option value="semana" {{ $periodo === 'semana' ? 'selected' : '' }}>Esta semana</option>
                <option value="mes"    {{ $periodo === 'mes'    ? 'selected' : '' }}>Este mes</option>
                <option value="rango"  {{ $periodo === 'rango'  ? 'selected' : '' }}>Rango libre</option>
            </select>
        </div>

        @if($periodo === 'rango')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                       class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       class="w-full sm:w-auto border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button type="submit"
                    class="col-span-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition">
                Filtrar
            </button>
        @endif
    </form>
</x-filtros>

{{-- Resumen del período --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-5">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4">
        <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Viajes en período</p>
        <p class="text-2xl sm:text-3xl font-bold text-blue-800 mt-1">{{ $cantidadViajes }}</p>
        <div class="mt-2 flex flex-wrap gap-2 text-xs">
            <span id="bd-cant-cobrados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">
                {{ $cantidadCobrados }} cobrados
            </span>
            <span id="bd-cant-no-cobrados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 font-medium">
                {{ $cantidadNoCobrados }} sin cobrar
            </span>
        </div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-lg p-3 sm:p-4">
        <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Total del período</p>
        <p class="text-lg sm:text-2xl font-bold text-green-800 mt-1 whitespace-nowrap">$ {{ number_format($totalPeriodo, 2, ',', '.') }}</p>
        <div class="mt-2 space-y-0.5 text-xs">
            <p class="flex justify-between text-green-700">
                <span>Cobrado</span>
                <span id="bd-total-cobrado" class="font-semibold whitespace-nowrap">$ {{ number_format($totalCobrado, 2, ',', '.') }}</span>
            </p>
            <p class="flex justify-between text-gray-600">
                <span>Sin cobrar</span>
                <span id="bd-total-no-cobrado" class="font-semibold whitespace-nowrap">$ {{ number_format($totalNoCobrado, 2, ',', '.') }}</span>
            </p>
            @if($totalAlquiler > 0 || $totalComision > 0)
                <div class="pt-1 mt-1 border-t border-green-200"></div>
                @if($totalAlquiler > 0)
                    <p class="flex justify-between text-amber-700">
                        <span><span class="sm:hidden">Equipo</span><span class="hidden sm:inline">Para el dueño del equipo:</span></span>
                        <span class="font-semibold whitespace-nowrap">−$ {{ number_format($totalAlquiler, 2, ',', '.') }}</span>
                    </p>
                @endif
                @if($totalComision > 0)
                    <p class="flex justify-between text-purple-700">
                        <span><span class="sm:hidden">Chofer</span><span class="hidden sm:inline">Para el chofer:</span></span>
                        <span class="font-semibold whitespace-nowrap">−$ {{ number_format($totalComision, 2, ',', '.') }}</span>
                    </p>
                @endif
                <p class="flex justify-between text-green-800">
                    <span>Te queda</span>
                    <span class="font-semibold whitespace-nowrap">$ {{ number_format($totalPeriodo - $totalAlquiler - $totalComision, 2, ',', '.') }}</span>
                </p>
            @endif
        </div>
    </div>
    <div class="hidden sm:flex bg-gray-50 border border-gray-200 rounded-lg p-4 items-center">
        <p class="text-sm text-gray-600">
            {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </p>
    </div>
</div>

{{--
    En el celular, los viajes agrupados por día: casi siempre son varios al
    día y a la misma ruta, así que el día lleva la fecha, el total y lo que
    queda, y cada viaje sólo lo que lo distingue (destino, peso, orden). Tocar
    un viaje abre Editar; la pastilla marca si se cobró. Repetir y Eliminar
    están en Editar, y "+ Otro viaje igual" repite el último del día.
--}}
@php
    $porDia = $viajes->groupBy(fn ($viaje) => $viaje->fecha->toDateString());
@endphp
<div class="sm:hidden">
    @if($viajes->isEmpty())
        <div class="bg-white rounded-lg shadow text-center py-10 text-sm text-gray-500">No hay viajes en este período.</div>
    @else
        <div class="relative mb-4">
            <input type="search" id="buscar-viajes" placeholder="Buscar cliente, destino, orden…" autocomplete="off"
                   class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        <div class="space-y-5">
            @foreach($porDia as $dia => $delDia)
                @php
                    $fecha = \Carbon\Carbon::parse($dia);
                    $etiquetaDia = $fecha->isToday() ? 'Hoy' : ($fecha->isYesterday() ? 'Ayer' : ucfirst($fecha->translatedFormat('l')));
                    $totalDia = $delDia->sum('total');
                    $netoDia = $delDia->sum(fn ($viaje) => $viaje->netoCamion());
                @endphp
                <section class="dia-viajes" aria-label="{{ $etiquetaDia }} {{ $fecha->format('d/m') }}">
                    <div class="flex items-baseline justify-between px-1 mb-1.5">
                        <h2 class="text-xs font-bold uppercase tracking-wide text-gray-700">{{ $etiquetaDia }} {{ $fecha->format('d/m') }}</h2>
                        <p class="text-xs text-gray-500">
                            {{ $delDia->count() }} viaje{{ $delDia->count() === 1 ? '' : 's' }} ·
                            <span class="font-bold text-gray-900">$ {{ number_format($totalDia, 2, ',', '.') }}</span>
                        </p>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden divide-y divide-gray-100">
                        @foreach($delDia as $viaje)
                            @php
                                $titulo = collect([$viaje->destino ?: $viaje->origen, $viaje->cargaCorta() ?? $viaje->producto])
                                    ->filter()->implode(' · ');
                                // Sin destino ni carga, el título es el cliente: no se repite abajo.
                                $clienteEnTitulo = $titulo === '';
                                $titulo = $titulo ?: ($viaje->cliente?->nombre ?? 'Viaje');
                                $detalle = collect([
                                    $viaje->fecha->format('H:i') !== '00:00' ? $viaje->fecha->format('H:i') : null,
                                    $clienteEnTitulo ? null : $viaje->cliente?->nombre,
                                    $viaje->nro_orden ? '#' . $viaje->nro_orden : null,
                                ])->filter()->implode(' · ');
                            @endphp
                            <div class="viaje-movil flex items-start justify-between gap-3 px-3.5 py-3 cursor-pointer active:bg-gray-50"
                                 data-href="{{ route('viajes.edit', $viaje) }}">
                                <div class="min-w-0">
                                    <a href="{{ route('viajes.edit', $viaje) }}" class="block text-[15px] font-semibold text-gray-900">{{ $titulo }}</a>
                                    @if($detalle !== '')
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $detalle }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <span class="text-[15px] font-bold text-gray-900 whitespace-nowrap">$ {{ number_format($viaje->total, 2, ',', '.') }}</span>
                                    <button type="button" class="pastilla-cobrado text-[11px] font-semibold rounded-full px-2 py-0.5"
                                            data-url="{{ route('viajes.cobrado', $viaje) }}"
                                            data-cobrado-de="{{ $viaje->id }}"
                                            data-cobrado="{{ $viaje->cobrado ? 1 : 0 }}"
                                            aria-pressed="{{ $viaje->cobrado ? 'true' : 'false' }}">
                                        {{ $viaje->cobrado ? 'Cobrado' : 'Sin cobrar' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach

                        <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 bg-gray-50">
                            @if($netoDia < $totalDia)
                                <span class="text-xs text-emerald-700">Te quedan $ {{ number_format($netoDia, 2, ',', '.') }}</span>
                            @else
                                <span></span>
                            @endif
                            <a href="{{ route('viajes.create', ['repetir' => $delDia->first()->id]) }}"
                               class="text-sm font-semibold text-blue-600 hover:text-blue-800">+ Otro viaje igual</a>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <p id="buscar-sin-resultados" class="hidden bg-white rounded-lg shadow text-center py-8 text-sm text-gray-500">No se encontraron viajes.</p>
    @endif
</div>

{{-- En la computadora, la tabla de siempre. --}}
<div class="hidden sm:block bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table id="tabla-viajes" class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Camión / Chofer</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">N° orden</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Carga</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Cobrado</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($viajes as $viaje)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                            {{ $viaje->camion?->patente ?? '—' }}
                            @if($viaje->chofer)
                                <p class="text-xs text-gray-500">{{ $viaje->chofer->nombre }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" data-order="{{ $viaje->fecha->timestamp }}">
                            {{ $viaje->fecha->format($viaje->fecha->format('H:i') === '00:00' ? 'd/m/Y' : 'd/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $viaje->cliente?->nombre ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700" data-order="{{ $viaje->nro_orden }}">{{ $viaje->nro_orden ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $viaje->ruta() }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $viaje->resumenCarga() }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap" data-order="{{ $viaje->total }}">
                            $ {{ number_format($viaje->total, 2, ',', '.') }}
                            @if($viaje->alquiler_monto > 0)
                                <p class="text-xs font-normal text-amber-700" title="Lo que se lleva el dueño de {{ $viaje->equipo?->nombre ?? 'el equipo' }}">
                                    −$ {{ number_format($viaje->alquiler_monto, 2, ',', '.') }} {{ $viaje->equipo?->nombre ?? 'equipo' }}
                                </p>
                            @endif
                            @if($viaje->comision_monto > 0)
                                <p class="text-xs font-normal text-purple-700" title="Comisión de {{ $viaje->chofer?->nombre ?? 'el chofer' }}{{ $viaje->liquidacion_id ? ' (ya liquidada)' : '' }}">
                                    −$ {{ number_format($viaje->comision_monto, 2, ',', '.') }} chofer{{ $viaje->liquidacion_id ? ' ✓' : '' }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center" data-order="{{ $viaje->cobrado ? 1 : 0 }}">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer toggle-cobrado"
                                       data-url="{{ route('viajes.cobrado', $viaje) }}"
                                       data-cobrado-de="{{ $viaje->id }}"
                                       aria-label="Cobrado"
                                       {{ $viaje->cobrado ? 'checked' : '' }}>
                                <span class="relative w-10 h-5 bg-gray-300 rounded-full transition-colors
                                             peer-checked:bg-green-500
                                             after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                             after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                                             peer-checked:after:translate-x-5"></span>
                            </label>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                {{-- Varios viajes por día con la misma ruta: sólo cambian la pesada y el peso. --}}
                                <a href="{{ route('viajes.create', ['repetir' => $viaje->id]) }}"
                                   title="Cargar otro viaje igual a éste"
                                   class="text-gray-700 hover:text-gray-900 font-medium text-xs px-2 py-1 rounded border border-gray-300 hover:bg-gray-100 transition">
                                    Repetir
                                </a>
                                <a href="{{ route('viajes.edit', $viaje) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('viajes.destroy', $viaje) }}"
                                      data-confirmar="¿Eliminar este viaje?"
                                      data-confirmar-detalle="{{ $viaje->resumenParaConfirmar() }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 font-medium text-xs px-2 py-1 rounded border border-red-200 hover:bg-red-50 transition">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#tabla-viajes').DataTable({
        pageLength: 25,
        pagingType: 'simple_numbers',
        lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'Todos']],
        order: [[1, 'desc']],
        createdRow: function(row) {
            $(row).removeClass('even:bg-gray-50 dark:even:bg-gray-900/50 odd:bg-white dark:odd:bg-gray-950');
        },
        columnDefs: [
            { orderable: false, targets: [7, 8] },
            { searchable: false, targets: [1, 6, 7, 8] },
        ],
        language: {
            decimal:        ',',
            thousands:      '.',
            emptyTable:     'No hay viajes en este período.',
            info:           'Mostrando _START_ a _END_ de _TOTAL_ viajes',
            infoEmpty:      'Mostrando 0 a 0 de 0 viajes',
            infoFiltered:   '(filtrado de _MAX_ en total)',
            lengthMenu:     'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing:     'Procesando...',
            search:         'Buscar:',
            zeroRecords:    'No se encontraron viajes.',
            paginate: {
                next:     '›',
                previous: '‹',
            },
        },
    });
});
</script>
@endpush

<script>
(function () {
    const bd = {
        cantC: {{ (int) $cantidadCobrados }},
        cantN: {{ (int) $cantidadNoCobrados }},
        totC: {{ (float) $totalCobrado }},
        totN: {{ (float) $totalNoCobrado }},
    };
    const totales = @json($viajes->pluck('total', 'id')->map(fn ($total) => (float) $total));
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : '';
    const nf = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function render() {
        document.getElementById('bd-cant-cobrados').textContent = bd.cantC + ' cobrados';
        document.getElementById('bd-cant-no-cobrados').textContent = bd.cantN + ' sin cobrar';
        document.getElementById('bd-total-cobrado').textContent = '$ ' + nf.format(bd.totC);
        document.getElementById('bd-total-no-cobrado').textContent = '$ ' + nf.format(bd.totN);
    }

    // La pastilla del celular: verde si se cobró, ámbar si no.
    const CLASES_COBRADO = ['bg-green-100', 'text-green-800'];
    const CLASES_SIN_COBRAR = ['bg-amber-100', 'text-amber-800'];

    function pintarPastilla(pastilla, cobrado) {
        pastilla.dataset.cobrado = cobrado ? '1' : '0';
        pastilla.setAttribute('aria-pressed', cobrado ? 'true' : 'false');
        pastilla.textContent = cobrado ? 'Cobrado' : 'Sin cobrar';
        pastilla.classList.remove(...CLASES_COBRADO, ...CLASES_SIN_COBRAR);
        pastilla.classList.add(...(cobrado ? CLASES_COBRADO : CLASES_SIN_COBRAR));
    }

    // Un mismo viaje está en la tabla y en la lista del celular: se muestran igual.
    function mostrarCobrado(id, cobrado) {
        document.querySelectorAll('[data-cobrado-de="' + id + '"]').forEach(function (el) {
            if (el.type === 'checkbox') {
                el.checked = cobrado;
            } else {
                pintarPastilla(el, cobrado);
            }
        });
    }

    function alternarCobrado(el, id, estabaCobrado) {
        el.disabled = true;

        fetch(el.dataset.url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(function (data) {
            const total = (typeof data.total === 'number') ? data.total : (totales[id] || 0);
            if (data.cobrado !== estabaCobrado) {
                if (data.cobrado) {
                    bd.cantC++; bd.cantN--; bd.totC += total; bd.totN -= total;
                } else {
                    bd.cantC--; bd.cantN++; bd.totC -= total; bd.totN += total;
                }
            }
            mostrarCobrado(id, data.cobrado);
            render();
        })
        .catch(function () {
            mostrarCobrado(id, estabaCobrado); // vuelve a como estaba
            const mensaje = 'No se pudo actualizar el estado de cobro. Probá de nuevo.';
            window.Swal ? Swal.fire({ icon: 'error', text: mensaje, confirmButtonColor: '#2563eb' }) : alert(mensaje);
        })
        .finally(function () { el.disabled = false; });
    }

    document.querySelectorAll('.toggle-cobrado').forEach(function (chk) {
        chk.addEventListener('change', function () {
            alternarCobrado(chk, chk.dataset.cobradoDe, ! chk.checked);
        });
    });

    document.querySelectorAll('.pastilla-cobrado').forEach(function (pastilla) {
        pintarPastilla(pastilla, pastilla.dataset.cobrado === '1');
        pastilla.addEventListener('click', function () {
            alternarCobrado(pastilla, pastilla.dataset.cobradoDe, pastilla.dataset.cobrado === '1');
        });
    });

    // Buscador del celular: filtra los viajes y esconde los días que quedan vacíos.
    const buscador = document.getElementById('buscar-viajes');
    const sinResultados = document.getElementById('buscar-sin-resultados');

    buscador?.addEventListener('input', function () {
        const texto = buscador.value.trim().toLowerCase();
        let visibles = 0;

        document.querySelectorAll('.dia-viajes').forEach(function (dia) {
            let delDia = 0;
            dia.querySelectorAll('.viaje-movil').forEach(function (viaje) {
                const coincide = texto === '' || viaje.textContent.toLowerCase().includes(texto);
                viaje.classList.toggle('hidden', ! coincide);
                if (coincide) delDia++;
            });
            dia.classList.toggle('hidden', delDia === 0);
            visibles += delDia;
        });

        sinResultados.classList.toggle('hidden', visibles > 0);
    });
})();
</script>
@endsection
