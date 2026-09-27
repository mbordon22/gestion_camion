@extends('layouts.app')

@section('title', 'Destinos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Destinos</h1>
    <a href="{{ route('destinos.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo destino
    </a>
</div>

@php $sinKm = $destinos->where('activo', true)->whereNull('km'); @endphp

@if($sinKm->isNotEmpty())
    <div class="mb-5 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">
            {{ $sinKm->count() === 1 ? 'Hay 1 destino sin distancia cargada.' : 'Hay ' . $sinKm->count() . ' destinos sin distancia cargada.' }}
        </p>
        <p class="mt-1 text-amber-800">
            Sin los km el viaje no puede completar la distancia solo: {{ $sinKm->pluck('nombre')->implode(', ') }}.
        </p>
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($destinos->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay destinos cargados.</p>
            <p class="text-sm mt-1">También podés crearlos desde el formulario de viaje.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Destino</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Origen</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Distancia</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($destinos as $destino)
                        <tr class="hover:bg-gray-50 transition {{ $destino->activo ? '' : 'opacity-50' }}">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $destino->nombre }}
                                @if($destino->notas)
                                    <p class="text-xs font-normal text-gray-500 truncate max-w-xs" title="{{ $destino->notas }}">{{ $destino->notas }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $destino->cliente?->nombre ?? 'Cualquiera' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $destino->origen ?? '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-medium {{ $destino->km === null ? 'text-amber-700' : 'text-gray-800' }}">
                                {{ $destino->kmFormateado() }}
                            </td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $destino->viajes_count }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($destino->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('destinos.edit', $destino) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('destinos.destroy', $destino) }}"
                                          onsubmit="return confirm('¿Eliminar este destino? Los viajes ya cargados no se tocan.')">
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
    @endif
</div>
@endsection
