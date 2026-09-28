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
</div>
@endsection
