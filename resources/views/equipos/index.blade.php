@extends('layouts.app')

@section('title', 'Equipos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Equipos</h1>
        <p class="text-sm text-gray-500 mt-1">Cisternas, equipos cañeros y acoplados. Si son alquilados, cuánto se lleva el dueño.</p>
    </div>
    <a href="{{ route('equipos.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo equipo
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($equipos->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay equipos cargados.</p>
            <p class="text-sm mt-1">Cargá la cisterna o el equipo cañero para elegirlo en cada viaje.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Equipo</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Camión</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Condición</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Le debés al dueño</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($equipos as $equipo)
                        <tr class="hover:bg-gray-50 transition {{ $equipo->activo ? '' : 'opacity-50' }}" data-href="{{ route('equipos.edit', $equipo) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">
                                {{ $equipo->etiqueta() }}
                                @if($equipo->tipo)
                                    <p class="text-xs font-normal text-gray-500">{{ $equipo->tipo }}</p>
                                @endif
                            </td>
                            <td class="t-dato {{ $equipo->camion ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap">{{ $equipo->camion?->patente ?? '—' }}</td>
                            <td class="t-dato px-4 py-3 text-gray-700">
                                {{ $equipo->condicion() }}
                                @if($equipo->alquilado)
                                    <span class="sm:block text-xs text-gray-500"><span class="sm:hidden">·</span> Dueño: {{ $equipo->propietario ?: 'sin cargar' }}</span>
                                @endif
                            </td>
                            <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $equipo->viajes_count }}<span class="sm:hidden"> viaje{{ $equipo->viajes_count === 1 ? '' : 's' }}</span></td>
                            <td class="t-monto {{ $equipo->sin_pagar > 0 ? '' : 't-ocultar' }} px-4 py-3 text-right whitespace-nowrap">
                                @if($equipo->sin_pagar > 0)
                                    <span class="font-semibold text-amber-700">$ {{ number_format($equipo->sin_pagar, 2, ',', '.') }}</span>
                                    <p class="text-xs font-normal text-gray-500">{{ $equipo->viajes_sin_pagar }} viaje{{ $equipo->viajes_sin_pagar !== 1 ? 's' : '' }}<span class="sm:hidden"> sin pagar</span></p>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="t-dato {{ $equipo->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($equipo->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    @if($equipo->alquilado || $equipo->sin_pagar > 0)
                                        <a href="{{ route('equipos.pagos', $equipo) }}"
                                           class="text-gray-700 hover:text-gray-900 font-medium text-sm sm:text-xs px-3 py-1.5 sm:px-2 sm:py-1 rounded border border-gray-300 hover:bg-gray-100 transition whitespace-nowrap">
                                            Pagos al dueño
                                        </a>
                                    @endif
                                    <a href="{{ route('equipos.edit', $equipo) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('equipos.destroy', $equipo) }}"
                                          data-confirmar="¿Eliminar este equipo?" data-confirmar-detalle="Si tiene viajes cargados, se desactiva en lugar de borrarse.">
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
