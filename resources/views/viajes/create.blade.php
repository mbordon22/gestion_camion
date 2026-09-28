@extends('layouts.app')

@php
    $desdeSimulacion = $desdeSimulacion ?? false;
    $repitiendo = isset($viaje) && ! $desdeSimulacion;
@endphp

@section('title', $repitiendo ? 'Repetir Viaje' : 'Nuevo Viaje')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('viajes.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ $repitiendo ? 'Repetir Viaje' : 'Nuevo Viaje' }}</h1>
    </div>

    @if($desdeSimulacion)
        <div class="mb-5 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <p class="font-semibold">Viene del simulador: ya están el cliente, la carga, el precio y la ruta.</p>
            <p class="mt-1 text-blue-800">Revisá la fecha y completá lo que trae el ticket: el número de orden y el peso real.</p>
        </div>
    @elseif($repitiendo)
        <div class="mb-5 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            <p class="font-semibold">Ya vienen cargados el camión, el cliente, el chofer, la carga y la ruta{{ $viaje->destino ? ' a ' . $viaje->destino : '' }}.</p>
            <p class="mt-1 text-blue-800">Falta lo que trae el ticket: el número de orden y el peso. Revisá que la fecha sea la correcta.</p>
        </div>
    @elseif($ultimo)
        {{-- Quien hace siempre el mismo recorrido, carga el viaje de un toque. --}}
        <a href="{{ route('viajes.create', ['repetir' => $ultimo->id]) }}"
           class="mb-5 flex items-center justify-between gap-3 rounded-lg border border-blue-200 bg-white px-4 py-3 shadow-sm hover:bg-blue-50 transition">
            <span class="min-w-0">
                <span class="block text-sm font-semibold text-gray-800">¿Igual que el último viaje?</span>
                <span class="block text-xs text-gray-500 truncate">
                    {{ collect([
                        $ultimo->cliente?->nombre,
                        $ultimo->ruta(),
                        $ultimo->esMontoFijo() ? '$ ' . number_format($ultimo->total, 0, ',', '.') : $ultimo->resumenCarga(),
                    ])->reject(fn ($parte) => blank($parte) || $parte === '—')->implode(' · ') }}
                </span>
            </span>
            <span class="flex-shrink-0 text-sm font-medium text-blue-700">Repetir →</span>
        </a>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('viajes.store') }}">
            @csrf
            @include('viajes._form')

            {{-- En el celular el botón queda siempre a mano, pegado abajo. --}}
            <div class="sticky bottom-0 -mx-6 mt-4 px-6 py-3 bg-white border-t border-gray-200 flex gap-3
                        sm:static sm:mx-0 sm:px-0 sm:py-0 sm:pt-4 sm:border-0">
                <button type="submit"
                        class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded shadow transition">
                    Guardar viaje
                </button>
                <a href="{{ route('viajes.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
