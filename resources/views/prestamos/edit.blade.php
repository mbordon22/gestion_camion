@extends('layouts.app')

@section('title', 'Editar Préstamo')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('prestamos.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Editar Préstamo</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('prestamos.update', $prestamo) }}">
            @csrf
            @method('PUT')
            @include('prestamos._form')

            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2 rounded shadow transition">
                    Actualizar préstamo
                </button>
                <a href="{{ route('prestamos.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-6 py-2 rounded transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @include('prestamos.cuotas', ['prestamo' => $prestamo])
</div>
@endsection
