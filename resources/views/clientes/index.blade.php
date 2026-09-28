@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Clientes</h1>
    <a href="{{ route('clientes.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo cliente
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($clientes->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay clientes cargados.</p>
            <p class="text-sm mt-1">También podés crearlos desde el formulario de viaje.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">CUIT</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Teléfono</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Sin cobrar</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($clientes as $cliente)
                        <tr class="hover:bg-gray-50 transition {{ $cliente->activo ? '' : 'opacity-50' }}" data-href="{{ route('clientes.edit', $cliente) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">
                                {{ $cliente->nombre }}
                                @if($cliente->notas)
                                    <p class="text-xs font-normal text-gray-500 truncate max-w-xs" title="{{ $cliente->notas }}">{{ $cliente->notas }}</p>
                                @endif
                            </td>
                            <td class="t-dato {{ $cliente->cuit ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap"><span class="sm:hidden">CUIT</span> {{ $cliente->cuit ?? '—' }}</td>
                            <td class="t-dato {{ $cliente->telefono ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap">{{ $cliente->telefono ?? '—' }}</td>
                            <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $cliente->viajes_count }}<span class="sm:hidden"> viaje{{ $cliente->viajes_count === 1 ? '' : 's' }}</span></td>
                            <td class="t-monto {{ $cliente->sin_cobrar > 0 ? '' : 't-ocultar' }} px-4 py-3 text-right whitespace-nowrap font-semibold {{ $cliente->sin_cobrar > 0 ? 'text-amber-700' : 'text-gray-400' }}">
                                $ {{ number_format($cliente->sin_cobrar ?? 0, 2, ',', '.') }}
                                <span class="block sm:hidden text-xs font-normal text-gray-500">sin cobrar</span>
                            </td>
                            <td class="t-dato {{ $cliente->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($cliente->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    @if($cliente->viajes_count > 0)
                                        {{-- Todos sus viajes, desde el primero: el listado ya suma cobrado y sin cobrar. --}}
                                        <a href="{{ route('viajes.index', [
                                                'cliente_id' => $cliente->id,
                                                'periodo'    => 'rango',
                                                'desde'      => \Carbon\Carbon::parse($cliente->viajes_min_fecha)->toDateString(),
                                                'hasta'      => today()->toDateString(),
                                            ]) }}"
                                           class="text-gray-700 hover:text-gray-900 font-medium text-sm sm:text-xs px-3 py-1.5 sm:px-2 sm:py-1 rounded border border-gray-300 hover:bg-gray-100 transition whitespace-nowrap">
                                            Ver viajes
                                        </a>
                                    @endif
                                    <a href="{{ route('clientes.edit', $cliente) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                                          data-confirmar="¿Eliminar este cliente?" data-confirmar-detalle="Si tiene viajes cargados, se desactiva en lugar de borrarse.">
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
