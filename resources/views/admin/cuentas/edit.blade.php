@extends('layouts.app')

@section('title', $cuenta->nombre)

@section('content')
@php
    $campo = 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400';
    $etiqueta = 'block text-sm font-medium text-gray-700 mb-1';
    $esLaMia = $cuenta->id === auth()->user()->cuenta_id;
    $usuarioNuevo = $errors->getBag('usuarioNuevo');
@endphp
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.cuentas.index') }}" class="text-blue-600 hover:text-blue-800" aria-label="Volver a las cuentas">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">{{ $cuenta->nombre }}</h1>
        @unless($cuenta->activa)
            <span class="inline-flex px-2 py-0.5 bg-gray-200 text-gray-600 rounded-full text-xs font-medium">Suspendida</span>
        @endunless
    </div>

    @include('admin.cuentas._credenciales')

    {{-- Cuánto la usa: sin ver sus datos, alcanza para saber si la está usando. --}}
    <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="bg-white rounded-lg shadow p-3 sm:p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Camiones</p>
            <p class="text-xl font-bold text-gray-800 mt-1">{{ $camiones }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 sm:p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Viajes</p>
            <p class="text-xl font-bold text-gray-800 mt-1">{{ $cantidadViajes }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 sm:p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Último viaje</p>
            <p class="text-sm font-semibold text-gray-800 mt-1">
                {{ $ultimoViaje ? \Carbon\Carbon::parse($ultimoViaje)->diffForHumans() : 'Ninguno' }}
            </p>
        </div>
    </div>

    {{-- La cuenta --}}
    <form method="POST" action="{{ route('admin.cuentas.update', $cuenta) }}" class="bg-white rounded-lg shadow p-6 space-y-4 mb-5">
        @csrf
        @method('PUT')
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">La cuenta</h2>
        <div>
            <label for="nombre" class="{{ $etiqueta }}">Nombre <span class="text-red-500">*</span></label>
            <input type="text" name="nombre" id="nombre" maxlength="100" required
                   value="{{ old('nombre', $cuenta->nombre) }}" class="{{ $campo }} @error('nombre') border-red-400 @enderror">
            @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="notas" class="{{ $etiqueta }}">Notas</label>
            <textarea name="notas" id="notas" rows="2" class="{{ $campo }}">{{ old('notas', $cuenta->notas) }}</textarea>
        </div>
        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 {{ $esLaMia ? 'opacity-60' : 'cursor-pointer' }}">
                <input type="hidden" name="activa" value="{{ $esLaMia ? 1 : 0 }}">
                <input type="checkbox" name="activa" value="1" {{ old('activa', $cuenta->activa) ? 'checked' : '' }} {{ $esLaMia ? 'disabled' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                Activa
            </label>
            <p class="text-xs text-gray-400 mt-1">
                {{ $esLaMia ? 'Es tu cuenta: no se puede suspender.' : 'Suspendida, sus usuarios no pueden entrar. No se borra nada y se puede volver a activar.' }}
            </p>
            @error('activa') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="border-t border-gray-100 pt-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-500">Qué usa</h2>
            <p class="text-xs text-gray-400 mt-1">Lo mismo que ve en su Configuración: lo apagado no le aparece.</p>
            @include('partials._funciones', ['elegidas' => $cuenta->funciones ?? []])
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded shadow transition">
            Guardar
        </button>
    </form>

    {{-- Sus usuarios --}}
    <div class="bg-white rounded-lg shadow mb-5">
        <h2 class="px-6 pt-5 pb-3 text-sm font-bold uppercase tracking-wide text-gray-500">Usuarios</h2>
        <ul class="divide-y divide-gray-100">
            @foreach($cuenta->usuarios as $usuario)
                @php $errores = $errors->getBag('contrasena' . $usuario->id); @endphp
                <li class="px-6 py-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-medium text-gray-800">
                            {{ $usuario->name }}
                            @if($usuario->esAdmin())
                                <span class="ml-1 inline-flex px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Administrador</span>
                            @endif
                        </p>
                        <p class="text-sm text-gray-500 break-all">{{ $usuario->email }}</p>
                    </div>
                    <details class="mt-2" {{ $errores->any() ? 'open' : '' }}>
                        <summary class="cursor-pointer text-sm text-blue-600 hover:text-blue-800">Cambiarle la contraseña</summary>
                        <form method="POST" action="{{ route('admin.usuarios.contrasena', $usuario) }}" class="mt-3 flex flex-col sm:flex-row gap-2">
                            @csrf
                            @method('PUT')
                            <div class="flex-1">
                                @include('admin.cuentas._contrasena', ['id' => 'password-' . $usuario->id, 'error' => $errores->first('password')])
                            </div>
                            <button type="submit" class="self-start bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded transition">
                                Cambiar
                            </button>
                        </form>
                    </details>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Otro usuario para la misma cuenta --}}
    <details class="bg-white rounded-lg shadow" {{ $usuarioNuevo->any() ? 'open' : '' }}>
        <summary class="cursor-pointer px-6 py-4 text-sm font-medium text-blue-700 hover:text-blue-900">+ Agregar otro usuario a esta cuenta</summary>
        <form method="POST" action="{{ route('admin.cuentas.usuarios.store', $cuenta) }}" class="px-6 pb-6 space-y-4">
            @csrf
            <p class="text-xs text-gray-500">Ve y carga lo mismo que los demás usuarios de la cuenta (por ejemplo, quien le lleva los papeles).</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="usuario" class="{{ $etiqueta }}">Nombre</label>
                    <input type="text" name="usuario" id="usuario" maxlength="255" required
                           value="{{ $usuarioNuevo->any() ? old('usuario') : '' }}" class="{{ $campo }}">
                    @if($usuarioNuevo->has('usuario')) <p class="text-red-500 text-xs mt-1">{{ $usuarioNuevo->first('usuario') }}</p> @endif
                </div>
                <div>
                    <label for="email" class="{{ $etiqueta }}">Correo</label>
                    <input type="email" name="email" id="email" maxlength="255" required
                           value="{{ $usuarioNuevo->any() ? old('email') : '' }}" class="{{ $campo }}">
                    @if($usuarioNuevo->has('email')) <p class="text-red-500 text-xs mt-1">{{ $usuarioNuevo->first('email') }}</p> @endif
                </div>
            </div>
            <div>
                <label for="password-agregar" class="{{ $etiqueta }}">Contraseña</label>
                @include('admin.cuentas._contrasena', ['id' => 'password-agregar', 'error' => $usuarioNuevo->first('password')])
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded shadow transition">
                Agregar usuario
            </button>
        </form>
    </details>
</div>
@endsection
