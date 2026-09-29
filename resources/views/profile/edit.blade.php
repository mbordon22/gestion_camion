@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Mi perfil</h1>

    <div class="space-y-6">
        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- Borrarse la cuenta no: las cuentas las maneja el administrador. --}}
    </div>
@endsection
