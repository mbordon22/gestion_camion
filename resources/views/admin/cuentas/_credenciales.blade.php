{{--
    Recién creado un usuario o cambiada su contraseña: los datos para
    pasarle al cliente. Se muestran una sola vez (vienen en la sesión).
--}}
@if(session('credenciales'))
    @php $credenciales = session('credenciales'); @endphp
    <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        <p class="font-semibold">Pasale estos datos para entrar</p>
        <dl class="mt-2 grid grid-cols-[auto,1fr] gap-x-3 gap-y-1">
            <dt class="text-blue-700">Dirección</dt><dd class="font-mono break-all">{{ url('/login') }}</dd>
            <dt class="text-blue-700">Correo</dt><dd class="font-mono break-all">{{ $credenciales['email'] }}</dd>
            <dt class="text-blue-700">Contraseña</dt><dd class="font-mono">{{ $credenciales['password'] }}</dd>
        </dl>
        <p class="mt-2 text-xs text-blue-700">Es la única vez que se muestra. Después la puede cambiar desde su perfil.</p>
    </div>
@endif
