@extends('layouts.app')

@section('title', 'Camiones')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Camiones</h1>
    <a href="{{ route('camiones.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo camión
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($camiones->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay camiones cargados.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Patente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Marca / Modelo</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Año</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($camiones as $camion)
                        <tr class="t-compacta hover:bg-gray-50 transition {{ $camion->activo ? '' : 'opacity-50' }}" data-href="{{ route('camiones.edit', $camion) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">{{ $camion->patente }}</td>
                            <td class="t-dato px-4 py-3 text-gray-700">
                                {{ trim(($camion->marca ?? '') . ' ' . ($camion->modelo ?? '')) ?: '—' }}
                            </td>
                            <td class="t-dato {{ $camion->anio ? '' : 't-ocultar' }} px-4 py-3 text-right text-gray-700">{{ $camion->anio ?? '—' }}</td>
                            <td class="t-dato {{ $camion->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($camion->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('camiones.edit', $camion) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('camiones.destroy', $camion) }}"
                                          data-confirmar="¿Eliminar este camión?" data-confirmar-detalle="Si tiene viajes o gastos cargados, se desactiva en lugar de borrarse.">
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
    @endif
</div>
@endsection
