@extends('layouts.app')

@section('title', 'Pagos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pagos / Vencimientos</h1>
        <p class="text-sm text-gray-500 mt-1">Lo que tenés que pagar: gastos a crédito, alquiler de equipos y choferes.</p>
    </div>
    <a href="{{ route('medios-pago.index') }}"
       class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded border border-gray-300 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Configurar medios
    </a>
</div>

{{-- Destacado: mes que viene --}}
<div class="bg-blue-600 text-white rounded-lg shadow p-5 mb-6">
    <p class="text-sm text-blue-100 uppercase tracking-wide">A pagar el mes que viene
        ({{ \Carbon\Carbon::createFromFormat('Y-m', $mesProximoKey)->translatedFormat('F Y') }})</p>
    <p class="text-3xl font-bold mt-1">$ {{ number_format($totalProximoMes, 2, ',', '.') }}</p>
</div>

{{-- Dueños de equipos alquilados --}}
@if($alquileres->isNotEmpty())
    <div class="bg-amber-50 border border-amber-300 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-amber-200 flex items-center justify-between">
            <h2 class="font-semibold text-amber-900">Alquiler de equipos sin pagar</h2>
            <span class="font-bold text-amber-900">$ {{ number_format($alquileres->sum('sin_pagar'), 2, ',', '.') }}</span>
        </div>
        <table class="tabla-tarjetas min-w-full divide-y divide-amber-100 text-sm">
            <tbody class="divide-y divide-amber-100">
                @foreach($alquileres as $equipo)
                    <tr class="t-compacta">
                        <td class="t-titulo px-5 py-2 text-gray-800 font-medium">{{ $equipo->etiqueta() }}</td>
                        <td class="t-dato px-5 py-2 text-gray-600">{{ $equipo->propietario ?: 'Dueño sin cargar' }}</td>
                        <td class="t-dato px-5 py-2 text-gray-500">{{ $equipo->viajes_sin_pagar }} viaje{{ $equipo->viajes_sin_pagar !== 1 ? 's' : '' }}</td>
                        <td class="t-monto px-5 py-2 text-right font-medium text-amber-900 whitespace-nowrap">$ {{ number_format($equipo->sin_pagar, 2, ',', '.') }}</td>
                        <td class="t-acciones px-5 py-2 text-right">
                            <a href="{{ route('equipos.pagos', $equipo) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-white transition whitespace-nowrap">
                                Registrar pago
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Choferes a comisión --}}
@if($choferes->isNotEmpty())
    <div class="bg-purple-50 border border-purple-300 rounded-lg shadow-sm mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-purple-200 flex items-center justify-between">
            <h2 class="font-semibold text-purple-900">Choferes sin liquidar</h2>
            <span class="font-bold text-purple-900">$ {{ number_format($choferes->sum('saldo'), 2, ',', '.') }}</span>
        </div>
        <table class="tabla-tarjetas min-w-full divide-y divide-purple-100 text-sm">
            <tbody class="divide-y divide-purple-100">
                @foreach($choferes as $chofer)
                    <tr class="t-compacta">
                        <td class="t-titulo px-5 py-2 text-gray-800 font-medium">{{ $chofer->nombre }}</td>
                        <td class="t-dato px-5 py-2 text-gray-500">
                            {{ $chofer->viajes_sin_liquidar }} viaje{{ $chofer->viajes_sin_liquidar !== 1 ? 's' : '' }}
                            @if($chofer->adelantos > 0)
                                · adelantos −$ {{ number_format($chofer->adelantos, 0, ',', '.') }}
                            @endif
                            @if($chofer->gastos > 0)
                                · gastos +$ {{ number_format($chofer->gastos, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="t-monto px-5 py-2 text-right font-medium whitespace-nowrap {{ $chofer->saldo >= 0 ? 'text-purple-900' : 'text-red-700' }}">
                            {{ $chofer->saldo < 0 ? '−' : '' }}$ {{ number_format(abs($chofer->saldo), 2, ',', '.') }}
                        </td>
                        <td class="t-acciones px-5 py-2 text-right">
                            <a href="{{ route('choferes.liquidacion', $chofer) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-white transition whitespace-nowrap">
                                Liquidar
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Meses --}}
@if($meses->isEmpty())
    <div class="bg-white rounded-lg shadow text-center py-12 text-gray-500">
        <p>No hay pagos pendientes de aquí en adelante.</p>
        <p class="text-sm mt-1">Los gastos a crédito aparecen acá según su fecha de cobro.</p>
    </div>
@else
    <div class="space-y-5">
        @foreach($meses as $mes)
            <div class="bg-white rounded-lg shadow overflow-hidden {{ $mes['esProximo'] ? 'ring-2 ring-blue-400' : '' }}">
                <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700 capitalize flex items-center gap-2">
                        {{ $mes['label'] }}
                        @if($mes['esActual'])
                            <span class="normal-case text-xs font-normal bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">este mes</span>
                        @elseif($mes['esProximo'])
                            <span class="normal-case text-xs font-normal bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">mes que viene</span>
                        @endif
                    </h2>
                    <span class="font-bold text-gray-800">$ {{ number_format($mes['total'], 2, ',', '.') }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-2 text-left font-semibold text-gray-600">Fecha</th>
                                <th class="px-5 py-2 text-left font-semibold text-gray-600">Concepto</th>
                                <th class="px-5 py-2 text-left font-semibold text-gray-600">Origen</th>
                                <th class="px-5 py-2 text-left font-semibold text-gray-600">Medio</th>
                                <th class="px-5 py-2 text-right font-semibold text-gray-600">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($mes['items'] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="t-pre px-5 py-2 whitespace-nowrap text-gray-700">
                                        <span class="hidden sm:inline">{{ $item['fecha']->format('d/m/Y') }}</span>
                                        <span class="sm:hidden">{{ $item['fecha']->format('d/m') }}</span>
                                    </td>
                                    <td class="t-titulo px-5 py-2 text-gray-700">{{ $item['detalle'] }}</td>
                                    <td class="t-dato px-5 py-2">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            {{ $item['origen'] }}
                                        </span>
                                    </td>
                                    <td class="t-dato px-5 py-2 text-gray-500">{{ $item['medio'] }}</td>
                                    <td class="t-monto px-5 py-2 text-right font-medium text-gray-900">$ {{ number_format($item['monto'], 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Subtotales por medio --}}
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-600">
                    <span class="font-medium text-gray-500">Por medio de pago:</span>
                    @foreach($mes['porMedio'] as $medio => $subtotal)
                        <span>{{ $medio }}: <strong class="text-gray-800">$ {{ number_format($subtotal, 2, ',', '.') }}</strong></span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
