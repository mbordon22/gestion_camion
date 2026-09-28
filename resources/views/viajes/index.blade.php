@extends('layouts.app')

@section('title', 'Viajes')
@section('container-class', 'w-full')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Viajes</h1>
    <a href="{{ route('viajes.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo viaje
    </a>
</div>

{{-- Filtro de período --}}
<div class="bg-white rounded-lg shadow p-4 mb-5">
    <form method="GET" action="{{ route('viajes.index') }}" class="flex flex-wrap gap-3 items-end">
        @if($clientes->isNotEmpty())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                <select name="cliente_id" onchange="this.form.submit()"
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                        class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                       class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition">
                Filtrar
            </button>
        @endif
    </form>
</div>

{{-- Resumen del período --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Viajes en período</p>
        <p class="text-3xl font-bold text-blue-800 mt-1">{{ $cantidadViajes }}</p>
        <div class="mt-2 flex flex-wrap gap-2 text-xs">
            <span id="bd-cant-cobrados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">
                {{ $cantidadCobrados }} cobrados
            </span>
            <span id="bd-cant-no-cobrados" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-200 text-gray-700 font-medium">
                {{ $cantidadNoCobrados }} sin cobrar
            </span>
        </div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Total del período</p>
        <p class="text-2xl font-bold text-green-800 mt-1">$ {{ number_format($totalPeriodo, 2, ',', '.') }}</p>
        <div class="mt-2 space-y-0.5 text-xs">
            <p class="flex justify-between text-green-700">
                <span>Cobrado:</span>
                <span id="bd-total-cobrado" class="font-semibold">$ {{ number_format($totalCobrado, 2, ',', '.') }}</span>
            </p>
            <p class="flex justify-between text-gray-600">
                <span>Sin cobrar:</span>
                <span id="bd-total-no-cobrado" class="font-semibold">$ {{ number_format($totalNoCobrado, 2, ',', '.') }}</span>
            </p>
            @if($totalAlquiler > 0 || $totalComision > 0)
                <div class="pt-1 mt-1 border-t border-green-200"></div>
                @if($totalAlquiler > 0)
                    <p class="flex justify-between text-amber-700">
                        <span>Para el dueño del equipo:</span>
                        <span class="font-semibold">−$ {{ number_format($totalAlquiler, 2, ',', '.') }}</span>
                    </p>
                @endif
                @if($totalComision > 0)
                    <p class="flex justify-between text-purple-700">
                        <span>Para el chofer:</span>
                        <span class="font-semibold">−$ {{ number_format($totalComision, 2, ',', '.') }}</span>
                    </p>
                @endif
                <p class="flex justify-between text-green-800">
                    <span>Te queda:</span>
                    <span class="font-semibold">$ {{ number_format($totalPeriodo - $totalAlquiler - $totalComision, 2, ',', '.') }}</span>
                </p>
            @endif
        </div>
    </div>
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-center">
        <p class="text-sm text-gray-600">
            {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </p>
    </div>
</div>

{{-- Tabla --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
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
                            {{ $viaje->fecha->format('d/m/Y H:i') }}
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
                                       data-total="{{ $viaje->total }}"
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
                                      onsubmit="return confirm('¿Eliminar este viaje?')">
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
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta ? meta.getAttribute('content') : '';
    const nf = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function render() {
        document.getElementById('bd-cant-cobrados').textContent = bd.cantC + ' cobrados';
        document.getElementById('bd-cant-no-cobrados').textContent = bd.cantN + ' sin cobrar';
        document.getElementById('bd-total-cobrado').textContent = '$ ' + nf.format(bd.totC);
        document.getElementById('bd-total-no-cobrado').textContent = '$ ' + nf.format(bd.totN);
    }

    document.querySelectorAll('.toggle-cobrado').forEach(function (chk) {
        chk.addEventListener('change', function () {
            const prev = !chk.checked; // estado antes del clic
            chk.disabled = true;

            fetch(chk.dataset.url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(function (data) {
                chk.checked = data.cobrado;
                const total = (typeof data.total === 'number') ? data.total : (parseFloat(chk.dataset.total) || 0);
                if (data.cobrado) {
                    bd.cantC++; bd.cantN--; bd.totC += total; bd.totN -= total;
                } else {
                    bd.cantC--; bd.cantN++; bd.totC -= total; bd.totN += total;
                }
                render();
            })
            .catch(function () {
                chk.checked = prev; // revertir el switch
                alert('No se pudo actualizar el estado de cobro. Probá de nuevo.');
            })
            .finally(function () { chk.disabled = false; });
        });
    });
})();
</script>
@endsection
