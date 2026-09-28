@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<h1 class="text-2xl font-bold text-gray-800 mb-4">Reportes — Rentabilidad del camión</h1>

{{-- Aclaración devengado vs caja --}}
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 mb-6 text-sm flex items-start gap-3">
    <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        Esta pantalla mide la <strong>rentabilidad</strong>: cuenta los gastos por la <strong>fecha en que se hicieron</strong>,
        no por cuándo se pagan. Para ver <strong>cuándo sale la plata</strong> (vencimientos de tarjetas)
        mirá <a href="{{ route('pagos.index') }}" class="font-semibold underline hover:text-amber-900">Pagos</a>.
    </div>
</div>

{{-- Selector de período --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('reportes.index') }}" class="flex flex-wrap gap-3 items-end">
        @if($camiones->count() > 1)
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
        @endif
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Período</label>
            <select name="periodo" onchange="this.form.submit()"
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="semana"   {{ $periodo === 'semana'   ? 'selected' : '' }}>Esta semana</option>
                <option value="quincena" {{ $periodo === 'quincena' ? 'selected' : '' }}>Esta quincena</option>
                <option value="mes"      {{ $periodo === 'mes'      ? 'selected' : '' }}>Este mes</option>
                <option value="rango"    {{ $periodo === 'rango'    ? 'selected' : '' }}>Rango libre</option>
            </select>
        </div>

        @if($periodo === 'rango')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}"
                       class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}"
                       class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition">
                Filtrar
            </button>
        @endif

        <p class="text-sm text-gray-500 self-end pb-2">
            {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
        </p>
    </form>
</div>

{{-- Tarjetas resumen --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 sm:col-span-1">
        <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Ingresos (viajes)</p>
        <p class="text-2xl font-bold text-green-800 mt-1">$ {{ number_format($totalIngresos, 2, ',', '.') }}</p>
        <p class="text-xs text-green-600 mt-1">{{ $cantidadViajes }} viaje{{ $cantidadViajes !== 1 ? 's' : '' }}</p>
    </div>

    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <p class="text-xs text-orange-600 font-medium uppercase tracking-wide">Combustible</p>
        <p class="text-2xl font-bold text-orange-800 mt-1">$ {{ number_format($totalCombustible, 2, ',', '.') }}</p>
        <p class="text-xs text-orange-600 mt-1">{{ number_format($litrosCargados, 1, ',', '.') }} L</p>
    </div>

    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-xs text-red-600 font-medium uppercase tracking-wide">Mantenimiento</p>
        <p class="text-2xl font-bold text-red-800 mt-1">$ {{ number_format($totalMantenimiento, 2, ',', '.') }}</p>
    </div>

    @if($totalAlquiler > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-xs text-amber-700 font-medium uppercase tracking-wide">Alquiler de equipos</p>
            <p class="text-2xl font-bold text-amber-800 mt-1">$ {{ number_format($totalAlquiler, 2, ',', '.') }}</p>
            <p class="text-xs text-amber-700 mt-1">La parte del dueño</p>
        </div>
    @endif

    @if($totalChofer > 0)
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <p class="text-xs text-purple-700 font-medium uppercase tracking-wide">Chofer</p>
            <p class="text-2xl font-bold text-purple-800 mt-1">$ {{ number_format($totalChofer, 2, ',', '.') }}</p>
            <p class="text-xs text-purple-700 mt-1">
                Comisiones $ {{ number_format($totalComision, 0, ',', '.') }}
                @if($totalGastosChofer > 0)
                    · gastos que pagó él $ {{ number_format($totalGastosChofer, 0, ',', '.') }}
                @endif
            </p>
        </div>
    @endif

    {{-- Con una tarjeta extra, "Total gastos" ocupa dos lugares para que el resultado arranque en su propia fila. --}}
    @php $extras = ($totalAlquiler > 0 ? 1 : 0) + ($totalChofer > 0 ? 1 : 0); @endphp
    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 {{ $extras === 1 ? 'sm:col-span-2' : '' }}">
        <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Total gastos</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">$ {{ number_format($totalGastos, 2, ',', '.') }}</p>
    </div>

    <div class="col-span-2 {{ $extras > 0 ? 'sm:col-span-3' : 'sm:col-span-2' }} rounded-lg p-4 border-2
                {{ $resultado >= 0 ? 'bg-blue-50 border-blue-300' : 'bg-red-50 border-red-300' }}">
        <p class="text-xs font-medium uppercase tracking-wide {{ $resultado >= 0 ? 'text-blue-600' : 'text-red-600' }}">
            Resultado neto (ingresos − gastos)
        </p>
        <p class="text-3xl font-bold mt-1 {{ $resultado >= 0 ? 'text-blue-800' : 'text-red-800' }}">
            {{ $resultado >= 0 ? '' : '−' }}$ {{ number_format(abs($resultado), 2, ',', '.') }}
        </p>
    </div>
</div>

{{-- Ingresos por cliente --}}
@if($porCliente->isNotEmpty())
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
        <h2 class="font-semibold text-gray-700">Por cliente</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Cobrado</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Sin cobrar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($porCliente as $fila)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            @if($fila->cliente)
                                <a href="{{ route('viajes.index', ['cliente_id' => $fila->cliente->id, 'camion_id' => $camionId, 'periodo' => 'rango', 'desde' => $desde, 'hasta' => $hasta]) }}"
                                   class="text-blue-600 hover:text-blue-800 hover:underline">{{ $fila->cliente->nombre }}</a>
                            @else
                                <span class="text-gray-500">Sin cliente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $fila->viajes }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">$ {{ number_format($fila->total, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-green-700">$ {{ number_format($fila->cobrado, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $fila->sinCobrar > 0 ? 'text-amber-700' : 'text-gray-400' }}">$ {{ number_format($fila->sinCobrar, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Equipos alquilados: cuánto queda para el camión y cuánto para el dueño --}}
@if($porEquipo->isNotEmpty())
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
        <h2 class="font-semibold text-gray-700">Equipos alquilados</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Equipo</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total viajes</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Para el dueño</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Para el camión</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Sin pagarle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($porEquipo as $fila)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('equipos.pagos', $fila->equipo) }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $fila->equipo->etiqueta() }}</a>
                            <p class="text-xs font-normal text-gray-500">{{ $fila->equipo->propietario ?: 'Dueño sin cargar' }}</p>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $fila->viajes }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">$ {{ number_format($fila->bruto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-700">$ {{ number_format($fila->alquiler, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">$ {{ number_format($fila->bruto - $fila->alquiler, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right {{ $fila->sinPagar > 0 ? 'font-semibold text-amber-700' : 'text-gray-400' }}">$ {{ number_format($fila->sinPagar, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Choferes a comisión: cuánto generó cada uno y cuánto se llevó --}}
@if($porChofer->isNotEmpty())
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
        <h2 class="font-semibold text-gray-700">Choferes a comisión</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Chofer</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total viajes</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Comisión</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Gastos que pagó</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Sin liquidar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($porChofer as $fila)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('choferes.liquidacion', $fila->chofer) }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $fila->chofer->nombre }}</a>
                            <p class="text-xs font-normal text-gray-500">{{ $fila->chofer->condicion() }}</p>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $fila->viajes }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">$ {{ number_format($fila->bruto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-purple-700">$ {{ number_format($fila->comision, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right {{ $fila->gastos > 0 ? 'text-gray-700' : 'text-gray-400' }}">$ {{ number_format($fila->gastos, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right {{ $fila->sinLiquidar > 0 ? 'font-semibold text-amber-700' : 'text-gray-400' }}">$ {{ number_format($fila->sinLiquidar, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Tabla de viajes del período --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h2 class="font-semibold text-gray-700">Viajes del período</h2>
        <span class="text-sm text-gray-500">{{ $cantidadViajes }} registro{{ $cantidadViajes !== 1 ? 's' : '' }}</span>
    </div>

    @if($viajes->isEmpty())
        <div class="text-center py-10 text-gray-500 text-sm">
            Sin viajes en este período.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">N° orden</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Carga</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Total</th>
                        @if($totalAlquiler > 0)
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Alquiler equipo</th>
                        @endif
                        @if($totalComision > 0)
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Chofer</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($viajes as $viaje)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ $viaje->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $viaje->cliente?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $viaje->nro_orden ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $viaje->ruta() }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $viaje->resumenCarga() }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900">$ {{ number_format($viaje->total, 2, ',', '.') }}</td>
                            @if($totalAlquiler > 0)
                                <td class="px-4 py-3 text-right text-amber-700">
                                    {{ $viaje->alquiler_monto > 0 ? '−$ ' . number_format($viaje->alquiler_monto, 2, ',', '.') : '—' }}
                                </td>
                            @endif
                            @if($totalComision > 0)
                                <td class="px-4 py-3 text-right text-purple-700">
                                    {{ $viaje->comision_monto > 0 ? '−$ ' . number_format($viaje->comision_monto, 2, ',', '.') : '—' }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-semibold">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right text-gray-700">Total ingresos:</td>
                        <td class="px-4 py-3 text-right text-green-700">$ {{ number_format($totalIngresos, 2, ',', '.') }}</td>
                        @if($totalAlquiler > 0)
                            <td class="px-4 py-3 text-right text-amber-700">−$ {{ number_format($totalAlquiler, 2, ',', '.') }}</td>
                        @endif
                        @if($totalComision > 0)
                            <td class="px-4 py-3 text-right text-purple-700">−$ {{ number_format($totalComision, 2, ',', '.') }}</td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
