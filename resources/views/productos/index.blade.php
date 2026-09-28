@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Productos</h1>
        <p class="text-sm text-gray-500 mt-1">Lo que llevás en los viajes. Se elige al cargar el viaje y en las tarifas.</p>
    </div>
    <a href="{{ route('productos.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo producto
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($productos->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay productos cargados.</p>
            <p class="text-sm mt-1">También podés crearlos desde el formulario de viaje.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Producto</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Se mide en</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($productos as $producto)
                        <tr class="t-compacta hover:bg-gray-50 transition {{ $producto->activo ? '' : 'opacity-50' }}" data-href="{{ route('productos.edit', $producto) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">{{ $producto->nombre }}</td>
                            <td class="t-dato {{ $producto->unidad ? '' : 't-ocultar' }} px-4 py-3 text-gray-600"><span class="sm:hidden">en</span> {{ $producto->unidadEtiqueta() }}</td>
                            <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $producto->viajes_count }}<span class="sm:hidden"> viaje{{ $producto->viajes_count === 1 ? '' : 's' }}</span></td>
                            <td class="t-dato {{ $producto->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($producto->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('productos.edit', $producto) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('productos.destroy', $producto) }}"
                                          data-confirmar="¿Eliminar este producto?" data-confirmar-detalle="Si ya se usó en viajes o tarifas, se desactiva en lugar de borrarse.">
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
