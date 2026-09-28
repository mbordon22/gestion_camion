@extends('layouts.app')

@section('title', 'Editar Viaje')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('viajes.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Editar Viaje</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('viajes.update', $viaje) }}">
            @csrf
            @method('PUT')
            @include('viajes._form')

            {{-- En el celular el botón queda siempre a mano, pegado abajo. --}}
            <div class="sticky bottom-0 -mx-6 mt-4 px-6 py-3 bg-white border-t border-gray-200 flex gap-3
                        sm:static sm:mx-0 sm:px-0 sm:py-0 sm:pt-4 sm:border-0">
                <button type="submit"
                        class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded shadow transition">
                    Actualizar viaje
                </button>
                <a href="{{ route('viajes.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded transition text-center">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    {{--
        Lo que en el listado del celular no tiene botón propio: repetir este
        viaje puntual y borrarlo. Va afuera del formulario de arriba porque
        los formularios no se pueden anidar.
    --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('viajes.create', ['repetir' => $viaje->id]) }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.1 15a7 7 0 0011.9 2.6M18.9 9A7 7 0 007.1 6.4"/>
            </svg>
            Cargar otro igual
        </a>
        <form method="POST" action="{{ route('viajes.destroy', $viaje) }}"
              data-confirmar="¿Eliminar este viaje?"
              data-confirmar-detalle="{{ $viaje->resumenParaConfirmar() }}">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="text-sm font-medium text-red-600 hover:text-red-800 bg-white border border-red-200 hover:bg-red-50 px-4 py-2 rounded transition">
                Eliminar este viaje
            </button>
        </form>
    </div>
</div>
@endsection
