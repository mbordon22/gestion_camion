@extends('layouts.app')

@section('title', 'Choferes')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Choferes</h1>
    <a href="{{ route('choferes.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo chofer
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    @if($choferes->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <p>No hay choferes cargados.</p>
            <p class="text-sm mt-1">También podés crearlos desde el formulario de viaje.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Nombre</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">DNI</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Teléfono</th>
                        @usa('comisiones')
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Cómo cobra</th>
                        @endusa
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Último viaje</th>
                        @usa('comisiones')
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Le debés</th>
                        @endusa
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($choferes as $chofer)
                        <tr class="hover:bg-gray-50 transition {{ $chofer->activo ? '' : 'opacity-50' }}" data-href="{{ route('choferes.edit', $chofer) }}">
                            <td class="t-titulo px-4 py-3 font-medium text-gray-800">
                                {{ $chofer->nombre }}
                                @if($chofer->notas)
                                    <p class="text-xs font-normal text-gray-500 truncate max-w-xs" title="{{ $chofer->notas }}">{{ $chofer->notas }}</p>
                                @endif
                            </td>
                            <td class="t-dato {{ $chofer->dni ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap"><span class="sm:hidden">DNI</span> {{ $chofer->dni ?? '—' }}</td>
                            <td class="t-dato {{ $chofer->telefono ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap">{{ $chofer->telefono ?? '—' }}</td>
                            @usa('comisiones')
                                <td class="t-dato px-4 py-3 text-gray-700 whitespace-nowrap">{{ $chofer->condicion() }}</td>
                            @endusa
                            <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $chofer->viajes_count }}<span class="sm:hidden"> viaje{{ $chofer->viajes_count === 1 ? '' : 's' }}</span></td>
                            <td class="t-dato {{ $chofer->viajes_max_fecha ? '' : 't-ocultar' }} px-4 py-3 text-gray-700 whitespace-nowrap">
                                <span class="sm:hidden">último</span>
                                {{ $chofer->viajes_max_fecha ? \Carbon\Carbon::parse($chofer->viajes_max_fecha)->format('d/m/Y') : '—' }}
                            </td>
                            @usa('comisiones')
                                @php
                                    $saldo = $chofer->comisiones_sin_liquidar - $chofer->adelantos_sin_liquidar + $chofer->gastos_sin_liquidar;
                                @endphp
                                @php $conSaldo = $chofer->comisiones_sin_liquidar > 0 || $chofer->adelantos_sin_liquidar > 0 || $chofer->gastos_sin_liquidar > 0; @endphp
                                <td class="t-monto {{ $conSaldo ? '' : 't-ocultar' }} px-4 py-3 text-right whitespace-nowrap">
                                    @if($conSaldo)
                                        <span class="font-semibold {{ $saldo >= 0 ? 'text-amber-700' : 'text-red-700' }}">
                                            {{ $saldo < 0 ? '−' : '' }}$ {{ number_format(abs($saldo), 2, ',', '.') }}
                                        </span>
                                        @if($chofer->adelantos_sin_liquidar > 0)
                                            <p class="text-xs text-gray-500">ya descontados $ {{ number_format($chofer->adelantos_sin_liquidar, 0, ',', '.') }} de adelantos</p>
                                        @endif
                                        <span class="block sm:hidden text-xs font-normal text-gray-500">sin liquidar</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endusa
                            <td class="t-dato {{ $chofer->activo ? 't-ocultar' : '' }} px-4 py-3 text-center">
                                @if($chofer->activo)
                                    <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activo</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Inactivo</span>
                                @endif
                            </td>
                            <td class="t-acciones px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    @if(\App\Support\CuentaActual::usa('comisiones') && ($chofer->cobraPorViaje() || $chofer->movimientos_count > 0 || $chofer->liquidaciones_count > 0))
                                        <a href="{{ route('choferes.liquidacion', $chofer) }}"
                                           class="text-gray-700 hover:text-gray-900 font-medium text-sm sm:text-xs px-3 py-1.5 sm:px-2 sm:py-1 rounded border border-gray-300 hover:bg-gray-100 transition whitespace-nowrap">
                                            Liquidación
                                        </a>
                                    @endif
                                    @if($chofer->viajes_count > 0)
                                        {{-- Todos sus viajes, desde el primero. --}}
                                        <a href="{{ route('viajes.index', [
                                                'chofer_id' => $chofer->id,
                                                'periodo'   => 'rango',
                                                'desde'     => \Carbon\Carbon::parse($chofer->viajes_min_fecha)->toDateString(),
                                                'hasta'     => today()->toDateString(),
                                            ]) }}"
                                           class="text-gray-700 hover:text-gray-900 font-medium text-sm sm:text-xs px-3 py-1.5 sm:px-2 sm:py-1 rounded border border-gray-300 hover:bg-gray-100 transition whitespace-nowrap">
                                            Ver viajes
                                        </a>
                                    @endif
                                    <a href="{{ route('choferes.edit', $chofer) }}"
                                       class="hidden sm:inline-block text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition">
                                        Editar
                                    </a>
                                    <form method="POST" action="{{ route('choferes.destroy', $chofer) }}"
                                          data-confirmar="¿Eliminar este chofer?" data-confirmar-detalle="Si tiene viajes o pagos cargados, se desactiva en lugar de borrarse.">
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
