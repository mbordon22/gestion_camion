@extends('layouts.app')

@section('title', 'Cuentas')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Cuentas</h1>
        <p class="text-sm text-gray-500 mt-1">Cada cliente del sistema con sus usuarios. Cada uno ve sólo sus datos.</p>
    </div>
    <a href="{{ route('admin.cuentas.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva cuenta
    </a>
</div>

<div class="lista-tarjetas bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tabla-tarjetas min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Cuenta</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Usuarios</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Camiones</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Viajes</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Último viaje</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($cuentas as $cuenta)
                    @php
                        $deLaCuenta = $viajes->get($cuenta->id);
                        $ultimo = $deLaCuenta?->ultimo ? \Carbon\Carbon::parse($deLaCuenta->ultimo) : null;
                    @endphp
                    <tr class="t-compacta hover:bg-gray-50 transition cursor-pointer {{ $cuenta->activa ? '' : 'opacity-60' }}"
                        data-href="{{ route('admin.cuentas.edit', $cuenta) }}">
                        <td class="t-titulo px-4 py-3 font-medium text-gray-800">
                            <a href="{{ route('admin.cuentas.edit', $cuenta) }}" class="hover:underline">{{ $cuenta->nombre }}</a>
                            @if($cuenta->id === auth()->user()->cuenta_id)
                                <span class="ml-1 text-xs font-normal text-gray-500">(la tuya)</span>
                            @endif
                        </td>
                        <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $cuenta->usuarios_count }}<span class="sm:hidden"> usuario{{ $cuenta->usuarios_count === 1 ? '' : 's' }}</span></td>
                        <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $camiones->get($cuenta->id, 0) }}<span class="sm:hidden"> camion{{ $camiones->get($cuenta->id, 0) === 1 ? '' : 'es' }}</span></td>
                        <td class="t-dato px-4 py-3 text-right text-gray-700">{{ $deLaCuenta?->cantidad ?? 0 }}<span class="sm:hidden"> viajes</span></td>
                        <td class="t-dato px-4 py-3 text-gray-600">
                            <span class="sm:hidden">último</span>
                            {{ $ultimo ? $ultimo->format('d/m/Y') . ' (' . $ultimo->diffForHumans() . ')' : 'Todavía no cargó viajes' }}
                        </td>
                        <td class="t-dato px-4 py-3 text-center">
                            @if($cuenta->activa)
                                <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">Activa</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Suspendida</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
