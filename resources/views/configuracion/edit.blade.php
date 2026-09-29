@extends('layouts.app')

@section('title', 'Configuración')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-800">Configuración</h1>
    <p class="text-sm text-gray-500 mt-1 mb-6">
        Prendé sólo lo que usás: lo demás no aparece ni en el menú ni al cargar un viaje.
    </p>

    <form method="POST" action="{{ route('configuracion.update') }}" class="bg-white rounded-lg shadow px-6 pt-2 pb-6">
        @csrf
        @method('PUT')

        @include('partials._funciones', ['elegidas' => $cuenta->funciones ?? []])

        <p class="text-xs text-gray-500 border-t border-gray-100 pt-4">
            Si apagás algo que ya usaste, no se borra nada: lo que está cargado queda como está y
            los reportes lo siguen contando. Lo podés volver a prender cuando quieras.
        </p>

        <button type="submit" class="mt-5 bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded shadow transition">
            Guardar
        </button>
    </form>
</div>
@endsection
