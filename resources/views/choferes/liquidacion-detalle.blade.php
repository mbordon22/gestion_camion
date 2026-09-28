@extends('layouts.app')

@section('title', 'Liquidación ' . $liquidacion->chofer->nombre)

@section('content')
@php
    $chofer = $liquidacion->chofer;
    $viajes = $liquidacion->viajes;
    $movimientos = $liquidacion->movimientos;
    $pesos = fn ($monto) => '$ ' . number_format((float) $monto, 2, ',', '.');

    // El mismo resumen en texto, para mandárselo al chofer por WhatsApp.
    // El detalle de viajes va sólo si no es una lista eterna.
    $lineas = ["*Liquidación {$chofer->nombre}*", 'Pagada el ' . $liquidacion->fecha->format('d/m/Y'), ''];
    if ($viajes->isNotEmpty()) {
        $lineas[] = $viajes->count() . ' viaje' . ($viajes->count() !== 1 ? 's' : '')
            . ' del ' . $viajes->min('fecha')->format('d/m') . ' al ' . $viajes->max('fecha')->format('d/m/Y');
        if ($viajes->count() <= 40) {
            foreach ($viajes as $viaje) {
                $lineas[] = '• ' . $viaje->fecha->format('d/m')
                    . ($viaje->nro_orden ? ' · orden ' . $viaje->nro_orden : '')
                    . ' · ' . $pesos($viaje->comision_monto);
            }
        }
        $lineas[] = '';
    }
    $lineas[] = 'Comisiones: ' . $pesos($liquidacion->comisiones);
    foreach ($movimientos as $movimiento) {
        $lineas[] = ($movimiento->esAdelanto() ? 'Adelanto ' : 'Gasto ') . $movimiento->fecha->format('d/m')
            . ($movimiento->concepto ? ' (' . $movimiento->concepto . ')' : '')
            . ': ' . ($movimiento->esAdelanto() ? '−' : '+') . $pesos($movimiento->monto);
    }
    $lineas[] = '*Total: ' . $pesos($liquidacion->total) . '*';

    $whatsapp = $chofer->whatsapp();
    $urlWhatsapp = 'https://wa.me/' . ($whatsapp ?? '') . '?text=' . rawurlencode(implode("\n", $lineas));
@endphp

<style>
    @media print {
        nav, .no-print { display: none !important; }
        body { background: #fff; }
        .shadow { box-shadow: none !important; }
    }
</style>

<div class="max-w-4xl mx-auto">
    <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('choferes.liquidacion', $chofer) }}" class="text-blue-600 hover:text-blue-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="text-sm text-gray-500">Volver a la liquidación de {{ $chofer->nombre }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()"
                    class="bg-gray-700 hover:bg-gray-800 text-white font-medium text-sm px-4 py-2 rounded shadow transition">
                Imprimir
            </button>
            <a href="{{ $urlWhatsapp }}" target="_blank" rel="noopener"
               class="bg-green-600 hover:bg-green-700 text-white font-medium text-sm px-4 py-2 rounded shadow transition"
               title="{{ $whatsapp ? 'Se abre el chat con ' . $chofer->nombre : 'El chofer no tiene teléfono cargado: elegís a quién mandarlo' }}">
                Mandar por WhatsApp
            </a>
            <form method="POST" action="{{ route('liquidaciones.destroy', $liquidacion) }}"
                  onsubmit="return confirm('¿Deshacer esta liquidación? Sus viajes, adelantos y gastos vuelven a figurar como pendientes.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="text-red-600 hover:text-red-800 font-medium text-sm px-4 py-2 rounded border border-red-200 hover:bg-red-50 transition">
                    Deshacer
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between gap-4 border-b border-gray-200 pb-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Liquidación · {{ $chofer->nombre }}</h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $chofer->dni ? 'DNI ' . $chofer->dni . ' · ' : '' }}Pagada el {{ $liquidacion->fecha->format('d/m/Y') }}
                    @if($viajes->isNotEmpty())
                        · Viajes del {{ $viajes->min('fecha')->format('d/m') }} al {{ $viajes->max('fecha')->format('d/m/Y') }}
                    @endif
                </p>
                @if($liquidacion->notas)
                    <p class="text-sm text-gray-600 mt-1">{{ $liquidacion->notas }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total pagado</p>
                <p class="text-3xl font-bold text-gray-900">{{ $pesos($liquidacion->total) }}</p>
            </div>
        </div>

        @if($viajes->isNotEmpty())
            <h2 class="font-semibold text-gray-700 mb-2">Viajes</h2>
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Fecha</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">N° orden</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Ruta</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Carga</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-600">Total viaje</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-600">Comisión</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($viajes as $viaje)
                            <tr>
                                <td class="px-3 py-1.5 text-gray-700 whitespace-nowrap">{{ $viaje->fecha->format('d/m/Y') }}</td>
                                <td class="px-3 py-1.5 text-gray-700">{{ $viaje->nro_orden ?? '—' }}</td>
                                <td class="px-3 py-1.5 text-gray-600">{{ $viaje->ruta() }}</td>
                                <td class="px-3 py-1.5 text-gray-600">{{ $viaje->resumenCarga() }}</td>
                                <td class="px-3 py-1.5 text-right text-gray-700 whitespace-nowrap">{{ $pesos($viaje->total) }}</td>
                                <td class="px-3 py-1.5 text-right font-medium text-gray-900 whitespace-nowrap">
                                    {{ $pesos($viaje->comision_monto) }}
                                    @if($viaje->comision_porcentaje !== null)
                                        <span class="text-xs font-normal text-gray-500">({{ \App\Models\Equipo::porcentajeFormateado($viaje->comision_porcentaje) }}%)</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($movimientos->isNotEmpty())
            <h2 class="font-semibold text-gray-700 mb-2">Adelantos y gastos</h2>
            <table class="min-w-full divide-y divide-gray-200 text-sm mb-6">
                <tbody class="divide-y divide-gray-100">
                    @foreach($movimientos as $movimiento)
                        <tr>
                            <td class="px-3 py-1.5 text-gray-700 whitespace-nowrap w-28">{{ $movimiento->fecha->format('d/m/Y') }}</td>
                            <td class="px-3 py-1.5 text-gray-700">
                                {{ $movimiento->esAdelanto() ? 'Adelanto' : 'Gasto que pagó él' }}{{ $movimiento->concepto ? ' · ' . $movimiento->concepto : '' }}
                            </td>
                            <td class="px-3 py-1.5 text-right font-medium whitespace-nowrap {{ $movimiento->esAdelanto() ? 'text-red-700' : 'text-green-700' }}">
                                {{ $movimiento->esAdelanto() ? '−' : '+' }}{{ $pesos($movimiento->monto) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <dl class="ml-auto max-w-xs text-sm space-y-1">
            <div class="flex justify-between"><dt class="text-gray-600">Comisiones</dt><dd class="font-medium">{{ $pesos($liquidacion->comisiones) }}</dd></div>
            @if($liquidacion->adelantos > 0)
                <div class="flex justify-between"><dt class="text-gray-600">Adelantos</dt><dd class="font-medium text-red-700">−{{ $pesos($liquidacion->adelantos) }}</dd></div>
            @endif
            @if($liquidacion->gastos > 0)
                <div class="flex justify-between"><dt class="text-gray-600">Gastos a devolverle</dt><dd class="font-medium text-green-700">+{{ $pesos($liquidacion->gastos) }}</dd></div>
            @endif
            <div class="flex justify-between border-t border-gray-300 pt-1 text-base">
                <dt class="font-semibold text-gray-800">Total</dt><dd class="font-bold text-gray-900">{{ $pesos($liquidacion->total) }}</dd>
            </div>
        </dl>

        {{-- Si después se corrigió algún viaje, lo pagado sigue siendo lo de ese día. --}}
        @if(abs($viajes->sum('comision_monto') - (float) $liquidacion->comisiones) >= 0.01)
            <p class="no-print text-xs text-amber-700 mt-4">
                Algún viaje se editó o se borró después de liquidar: las comisiones de la lista suman
                {{ $pesos($viajes->sum('comision_monto')) }}, pero lo que se le pagó ese día fue {{ $pesos($liquidacion->comisiones) }}.
            </p>
        @endif
    </div>
</div>
@endsection
