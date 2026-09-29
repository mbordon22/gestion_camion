@extends('layouts.app')

@section('title', 'Nueva cuenta')

@section('content')
@php
    $campo = 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400';
    $etiqueta = 'block text-sm font-medium text-gray-700 mb-1';
@endphp
<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.cuentas.index') }}" class="text-blue-600 hover:text-blue-800" aria-label="Volver a las cuentas">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Nueva cuenta</h1>
    </div>

    <form method="POST" action="{{ route('admin.cuentas.store') }}" class="space-y-5">
        @csrf

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">El cliente</h2>
            <div>
                <label for="nombre" class="{{ $etiqueta }}">Nombre de la cuenta <span class="text-red-500">*</span></label>
                <input type="text" name="nombre" id="nombre" maxlength="100" required placeholder="Ej: Transportes Pérez"
                       value="{{ old('nombre') }}" class="{{ $campo }} @error('nombre') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">El transportista o la empresa. Es lo que ves vos en esta lista.</p>
                @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="patente" class="{{ $etiqueta }}">Patente de su camión</label>
                <input type="text" name="patente" id="patente" maxlength="20" placeholder="Ej: AB123CD"
                       value="{{ old('patente') }}" class="{{ $campo }} uppercase @error('patente') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Opcional. Si la cargás, entra y ya puede cargar viajes.</p>
                @error('patente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="notas" class="{{ $etiqueta }}">Notas</label>
                <textarea name="notas" id="notas" rows="2" placeholder="Cómo llegó, qué plan tiene, lo que quieras recordar…"
                          class="{{ $campo }} @error('notas') border-red-400 @enderror">{{ old('notas') }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Su usuario</h2>
            <div>
                <label for="usuario" class="{{ $etiqueta }}">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="usuario" id="usuario" maxlength="255" required placeholder="Ej: Juan Pérez"
                       value="{{ old('usuario') }}" class="{{ $campo }} @error('usuario') border-red-400 @enderror">
                @error('usuario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="{{ $etiqueta }}">Correo <span class="text-red-500">*</span></label>
                <input type="email" name="email" id="email" maxlength="255" required placeholder="juan@ejemplo.com"
                       value="{{ old('email') }}" class="{{ $campo }} @error('email') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Con este correo entra al sistema.</p>
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password-nueva" class="{{ $etiqueta }}">Contraseña <span class="text-red-500">*</span></label>
                @include('admin.cuentas._contrasena', ['id' => 'password-nueva', 'valor' => old('password'), 'error' => $errors->first('password')])
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded shadow transition">
                Crear cuenta
            </button>
            <a href="{{ route('admin.cuentas.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
