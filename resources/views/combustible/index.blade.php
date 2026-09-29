@extends('layouts.app')

@section('title', 'Combustible')
@section('container-class', 'w-full')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Combustible</h1>
    <a href="{{ route('combustible.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva carga
    </a>
</div>

{{-- Filtros. En el celular, plegados con lo elegido a la vista. --}}
@php
    $resumenFiltros = collect([
        $camiones->firstWhere('id', $camionId)?->nombre(),
        \Carbon\Carbon::parse($desde)->format('d/m') . ' al ' . \Carbon\Carbon::parse($hasta)->format('d/m'),
    ])->filter()->implode(' · ');
@endphp
<x-filtros :resumen="$resumenFiltros">
    <form method="GET" action="{{ route('combustible.index') }}" class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end">
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
            <button type="submit" class="col-span-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition">
                Filtrar
            </button>
        @endif
    </form>
</x-filtros>

{{-- Resumen --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mb-5">
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 sm:p-4">
        <p class="text-xs text-orange-600 font-medium uppercase tracking-wide">Litros cargados</p>
        <p class="text-xl sm:text-3xl font-bold text-orange-800 mt-1 whitespace-nowrap">{{ number_format($totalLitros, 1, ',', '.') }} L</p>
    </div>
    <div class="bg-red-50 border border-red-200 rounded-lg p-3 sm:p-4">
        <p class="text-xs text-red-600 font-medium uppercase tracking-wide">Gasto total</p>
        <p class="text-lg sm:text-2xl font-bold text-red-800 mt-1 whitespace-nowrap">$ {{ number_format($totalGasto, 2, ',', '.') }}</p>
    </div>
    <div class="hidden sm:flex bg-gray-50 border border-gray-200 rounded-lg p-4 items-center">
        <p class="text-sm text-gray-600">
            {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </p>
    </div>
</div>

{{-- Tabla --}}
<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table id="tabla-combustible" class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Camión</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Litros</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Precio/litro</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Km odómetro</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Lugar</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Medio</th>
                    @usa('tarjetas')
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha pago</th>
                    @endusa
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($registros as $reg)
                    @php
                        // En el celular: la fecha de pago sólo si no es la de la carga (a crédito).
                        $pagaOtroDia = $reg->fecha_vencimiento && ! $reg->fecha_vencimiento->isSameDay($reg->fecha);
                    @endphp
                    <tr class="t-compacta hover:bg-gray-50 transition" data-href="{{ route('combustible.edit', $reg) }}">
                        <td class="t-dato {{ $camiones->count() > 1 ? '' : 't-ocultar' }} px-4 py-3 text-gray-600">{{ $reg->camion?->patente ?? '—' }}</td>
                        <td class="t-pre px-4 py-3 whitespace-nowrap text-gray-700" data-order="{{ $reg->fecha->timestamp }}">
                            <span class="hidden sm:inline">{{ $reg->fecha->format('d/m/Y') }}</span>
                            <span class="sm:hidden">{{ $reg->fecha->format('d/m') }}</span>
                        </td>
                        <td class="t-dato px-4 py-3 text-right text-gray-700" data-order="{{ $reg->litros }}">
                            {{ number_format($reg->litros, 2, ',', '.') }} L
                        </td>
                        <td class="t-dato px-4 py-3 text-right text-gray-700" data-order="{{ $reg->precio_litro }}">
                            $ {{ number_format($reg->precio_litro, 2, ',', '.') }}<span class="sm:hidden">/L</span>
                        </td>
                        <td class="t-monto px-4 py-3 text-right font-semibold text-gray-900" data-order="{{ $reg->total }}">
                            $ {{ number_format($reg->total, 2, ',', '.') }}
                        </td>
                        <td class="t-dato {{ $reg->km_odometro ? '' : 't-ocultar' }} px-4 py-3 text-right text-gray-600" data-order="{{ $reg->km_odometro ?? 0 }}">
                            {{ $reg->km_odometro ? number_format($reg->km_odometro, 0, ',', '.') . ' km' : '—' }}
                        </td>
                        <td class="t-titulo px-4 py-3 text-gray-600">
                            <span class="hidden sm:inline">{{ $reg->lugar ?? '—' }}</span>
                            <span class="sm:hidden">{{ $reg->lugar ?? 'Carga de combustible' }}</span>
                        </td>
                        <td class="t-dato {{ $reg->medioPago ? '' : 't-ocultar' }} px-4 py-3 text-gray-600">{{ $reg->medioPago?->nombre ?? '—' }}</td>
                        @usa('tarjetas')
                            <td class="t-dato {{ $pagaOtroDia ? '' : 't-ocultar' }} px-4 py-3 whitespace-nowrap text-gray-600" data-order="{{ $reg->fecha_vencimiento ? $reg->fecha_vencimiento->timestamp : 0 }}">
                                <span class="sm:hidden">Se paga el</span>
                                {{ $reg->fecha_vencimiento ? $reg->fecha_vencimiento->format('d/m/Y') : '—' }}
                            </td>
                        @endusa
                        <td class="t-acciones px-4 py-3 text-center">
                            <div class="flex justify-center gap-2">
                                <a href="{{ route('combustible.edit', $reg) }}"
                                   class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('combustible.destroy', $reg) }}"
                                      data-confirmar="¿Eliminar esta carga?"
                                      data-confirmar-detalle="{{ $reg->fecha->format('d/m/Y') }} · {{ number_format($reg->litros, 2, ',', '.') }} L · $ {{ number_format($reg->total, 2, ',', '.') }}. No se puede deshacer.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 font-medium text-sm sm:text-xs px-3 py-1.5 sm:px-2 sm:py-1 rounded border border-red-200 hover:bg-red-50 transition">
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
    $('#tabla-combustible').DataTable({
        pageLength: 25,
        pagingType: 'simple_numbers',
        lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'Todos']],
        order: [[1, 'desc']],
        createdRow: function(row) {
            $(row).removeClass('even:bg-gray-50 dark:even:bg-gray-900/50 odd:bg-white dark:odd:bg-gray-950');
        },
        columnDefs: [
            { orderable: false, targets: [-1] },
            { searchable: false, targets: [-1] },
        ],
        language: {
            decimal:        ',',
            thousands:      '.',
            emptyTable:     'No hay registros de combustible en este período.',
            info:           'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty:      'Mostrando 0 a 0 de 0 registros',
            infoFiltered:   '(filtrado de _MAX_ en total)',
            lengthMenu:     'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando...',
            processing:     'Procesando...',
            search:         '',
            searchPlaceholder: 'Buscar lugar, medio de pago…',
            zeroRecords:    'No se encontraron registros.',
            paginate: {
                next:     '›',
                previous: '‹',
            },
        },
    });
});
</script>
@endpush
@endsection
