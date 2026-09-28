@extends('layouts.app')

@section('title', 'Medios de Pago')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('pagos.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Medios de Pago</h1>
    </div>
    <a href="{{ route('medios-pago.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo medio
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($medios->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay medios de pago cargados.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Día cierre</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Día venc.</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($medios as $medio)
                        <tr class="t-compacta hover:bg-gray-50 transition {{ $medio->activo ? '' : 'opacity-50' }}" data-href="{{ route('medios-pago.edit', $medio) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">{{ $medio->nombre }}</td>
                            <td class="t-dato px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @switch($medio->tipo)
                                        @case('credito') bg-purple-100 text-purple-800 @break
                                        @case('descuento') bg-amber-100 text-amber-800 @break
                                        @default bg-gray-100 text-gray-700
                                    @endswitch">
                                    {{ \App\Models\MedioPago::$tipos[$medio->tipo] ?? $medio->tipo }}
                                </span>
                            </td>
                            <td class="t-dato {{ $medio->dia_cierre ? '' : 't-ocultar' }} px-4 py-3 text-right text-gray-700"><span class="sm:hidden">cierra el</span> {{ $medio->dia_cierre ?? '—' }}</td>
                            <td class="t-dato {{ $medio->dia_vencimiento ? '' : 't-ocultar' }} px-4 py-3 text-right text-gray-700"><span class="sm:hidden">vence el</span> {{ $medio->dia_vencimiento ?? '—' }}</td>
                            <td class="t-dato {{ $medio->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($medio->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('medios-pago.edit', $medio) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('medios-pago.destroy', $medio) }}"
                                          data-confirmar="¿Eliminar este medio de pago?" data-confirmar-detalle="Si ya se usó en algún gasto, se desactiva en lugar de borrarse.">
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
