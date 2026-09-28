@extends('layouts.app')

@section('title', 'Tarifas')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Tarifas</h1>
    <a href="{{ route('tarifas.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva tarifa
    </a>
</div>

@if($tarifas->isEmpty())
    <div class="bg-white rounded-lg shadow text-center py-12 text-gray-500">
        <p>No hay tarifas cargadas.</p>
        <p class="text-sm mt-1">Cargá una por cada rango de distancia y el precio se va a proponer solo al cargar el viaje.</p>
    </div>
@else
    <p class="text-sm text-gray-500 mb-5">
        Al cargar un viaje, el precio por unidad se propone según el cliente, el producto y los km.
        Siempre lo podés pisar a mano.
    </p>

    @foreach($tarifas as $nombreCliente => $delCliente)
        <div class="bg-white rounded-lg shadow overflow-hidden mb-5">
            <h2 class="px-4 py-3 bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">{{ $nombreCliente }}</h2>

            <div class="overflow-x-auto">
                <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Producto</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Rango</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Importe</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Rige desde</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($delCliente as $tarifa)
                            @php $vigente = $tarifa->estaVigente(); @endphp
                            <tr class="t-compacta hover:bg-gray-50 transition {{ $vigente ? '' : 'opacity-60' }}" data-href="{{ route('tarifas.edit', $tarifa) }}">
                                <td class="t-titulo px-4 py-3 text-gray-700">
                                    {{ $tarifa->producto ?? 'Cualquier carga' }}
                                    @if($tarifa->notas)
                                        <p class="text-xs text-gray-500 truncate max-w-xs" title="{{ $tarifa->notas }}">{{ $tarifa->notas }}</p>
                                    @endif
                                </td>
                                <td class="t-dato px-4 py-3 text-gray-800 font-medium whitespace-nowrap">{{ $tarifa->rango() }}</td>
                                <td class="t-monto px-4 py-3 text-right whitespace-nowrap font-semibold text-gray-900">
                                    {{ $tarifa->precioPorUnidad() }}
                                </td>
                                <td class="t-dato px-4 py-3 text-gray-600 whitespace-nowrap"><span class="sm:hidden">desde el</span> {{ $tarifa->vigente_desde->format('d/m/Y') }}</td>
                                <td class="t-dato px-4 py-3 text-center">
                                    @if($vigente)
                                        <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Vigente</span>
                                    @elseif($tarifa->vigente_desde->isFuture())
                                        <span class="inline-flex px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Desde {{ $tarifa->vigente_desde->format('d/m') }}</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Anterior</span>
                                    @endif
                                </td>
                                <td class="t-acciones px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('tarifas.edit', $tarifa) }}"
                                           class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('tarifas.destroy', $tarifa) }}"
                                              data-confirmar="¿Eliminar esta tarifa?" data-confirmar-detalle="Los viajes ya cargados no se tocan.">
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
    @endforeach
@endif
@endsection
