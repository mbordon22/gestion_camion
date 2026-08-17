@extends('layouts.app')

@section('title', 'Inicio')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">¿Cómo venimos?</h1>
        <p class="text-sm text-gray-500 capitalize">{{ $mes }}</p>
    </div>

    @if($camiones->count() > 1)
        <form method="GET" action="{{ route('inicio') }}">
            <select name="camion_id" onchange="this.form.submit()"
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Todos los camiones</option>
                @foreach($camiones as $camion)
                    <option value="{{ $camion->id }}" {{ (string) $camionId === (string) $camion->id ? 'selected' : '' }}>
                        {{ $camion->nombre() }}
                    </option>
                @endforeach
            </select>
        </form>
    @endif
</div>

{{-- Acá va el bloque de alertas (vencimientos y próximo service) cuando se haga P0 #2. --}}

{{-- El número que importa --}}
<div class="rounded-lg p-6 mb-5 border-2 {{ $resultado >= 0 ? 'bg-blue-50 border-blue-300' : 'bg-red-50 border-red-300' }}">
    <p class="text-sm font-medium {{ $resultado >= 0 ? 'text-blue-600' : 'text-red-600' }}">
        {{ $resultado >= 0 ? 'Ganancia del mes' : 'Pérdida del mes' }}
    </p>
    <p class="text-4xl sm:text-5xl font-bold mt-1 {{ $resultado >= 0 ? 'text-blue-800' : 'text-red-800' }}">
        {{ $resultado >= 0 ? '' : '−' }}$ {{ number_format(abs($resultado), 2, ',', '.') }}
    </p>
    <p class="text-xs mt-2 {{ $resultado >= 0 ? 'text-blue-600' : 'text-red-600' }}">
        Lo que entró por viajes menos lo que se gastó en combustible y mantenimiento.
    </p>
</div>

{{-- Las tres cuentas de atrás --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Entró</p>
        <p class="text-2xl font-bold text-green-800 mt-1">$ {{ number_format($totalIngresos, 2, ',', '.') }}</p>
        <p class="text-xs text-green-700 mt-1">{{ $cantidadViajes }} viaje{{ $cantidadViajes !== 1 ? 's' : '' }} este mes</p>
    </div>

    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <p class="text-xs text-orange-600 font-medium uppercase tracking-wide">Salió</p>
        <p class="text-2xl font-bold text-orange-800 mt-1">$ {{ number_format($totalGastos, 2, ',', '.') }}</p>
        <p class="text-xs text-orange-700 mt-1">
            Combustible $ {{ number_format($totalCombustible, 0, ',', '.') }} ·
            Mantenimiento $ {{ number_format($totalMantenimiento, 0, ',', '.') }}
        </p>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-600 font-medium uppercase tracking-wide">Falta cobrar</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">$ {{ number_format($totalPorCobrar, 2, ',', '.') }}</p>
        <p class="text-xs text-gray-500 mt-1">De los viajes de este mes</p>
    </div>
</div>

{{-- Lo que más se usa, a un toque --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <a href="{{ route('viajes.create') }}"
       class="flex items-center justify-center gap-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-lg px-6 py-5 rounded-lg shadow transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Cargar viaje
    </a>
    <a href="{{ route('combustible.create') }}"
       class="flex items-center justify-center gap-3 bg-orange-500 hover:bg-orange-600 text-white font-semibold text-lg px-6 py-5 rounded-lg shadow transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Cargar combustible
    </a>
</div>

{{-- Últimos viajes --}}
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h2 class="font-semibold text-gray-700">Últimos viajes</h2>
        <a href="{{ route('viajes.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Ver todos</a>
    </div>

    @if($ultimosViajes->isEmpty())
        <div class="text-center py-10 px-4 text-gray-500 text-sm">
            <p>Todavía no cargaste viajes este mes.</p>
            <a href="{{ route('viajes.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Cargar el primero</a>
        </div>
    @else
        <ul class="divide-y divide-gray-100">
            @foreach($ultimosViajes as $viaje)
                <li class="px-5 py-3 flex items-center justify-between gap-4 hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ $viaje->ruta() }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $viaje->fecha->format('d/m/Y') }} · {{ $viaje->resumenCarga() }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-semibold text-gray-900">$ {{ number_format($viaje->total, 2, ',', '.') }}</p>
                        @if($viaje->cobrado)
                            <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Cobrado</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Sin cobrar</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
