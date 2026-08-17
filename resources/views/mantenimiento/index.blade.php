@extends('layouts.app')

@section('title', 'Mantenimiento')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Mantenimiento</h1>
    <a href="{{ route('mantenimiento.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo registro
    </a>
</div>

{{-- Filtro de camión --}}
@if($camiones->count() > 1)
    <div class="bg-white rounded-lg shadow p-4 mb-5">
        <form method="GET" action="{{ route('mantenimiento.index') }}" class="flex flex-wrap gap-3 items-end">
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
        </form>
    </div>
@endif

@if($proximoService)
    <div class="mb-5 bg-orange-50 border border-orange-300 text-orange-800 rounded-lg px-4 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <p class="font-semibold">Próximo service programado</p>
            <p class="text-sm mt-0.5">A los <strong>{{ number_format($proximoService, 0, ',', '.') }} km</strong></p>
        </div>
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    @if($registros->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            </svg>
            <p>No hay registros de mantenimiento.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Camión</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Monto</th>
                        {{-- <th class="px-4 py-3 text-right font-semibold text-gray-600">Km actuales</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Próximo service</th> --}}
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Detalle</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Medio</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha pago</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($registros as $reg)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-gray-600">{{ $reg->camion?->patente ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $reg->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ match($reg->tipo) {
                                        'service'   => 'bg-blue-100 text-blue-800',
                                        'aceite'    => 'bg-yellow-100 text-yellow-800',
                                        'filtros'   => 'bg-purple-100 text-purple-800',
                                        'neumaticos'=> 'bg-gray-100 text-gray-800',
                                        'frenos'    => 'bg-red-100 text-red-800',
                                        'repuesto'  => 'bg-green-100 text-green-800',
                                        default     => 'bg-gray-100 text-gray-700',
                                    } }}">
                                    {{ \App\Models\Mantenimiento::$tipos[$reg->tipo] ?? $reg->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">$ {{ number_format($reg->monto, 2, ',', '.') }}</td>
                            {{-- <td class="px-4 py-3 text-right text-gray-600">
                                {{ $reg->km_actuales ? number_format($reg->km_actuales, 0, ',', '.') . ' km' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($reg->proximo_service)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-orange-100 text-orange-800 rounded-full text-xs font-medium">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                                        </svg>
                                        {{ number_format($reg->proximo_service, 0, ',', '.') }} km
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td> --}}
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $reg->detalle }}">
                                {{ $reg->detalle ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $reg->medioPago?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $reg->fecha_vencimiento ? $reg->fecha_vencimiento->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('mantenimiento.edit', $reg) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('mantenimiento.destroy', $reg) }}"
                                          onsubmit="return confirm('¿Eliminar este registro?')">
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
