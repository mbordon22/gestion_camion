{{--
    Campo de contraseña del panel: se ve (el administrador se la tiene que
    pasar al cliente) y "Generar" propone una fácil de dictar por teléfono.
    Espera $id (único en la página) y, si hay, $error.
--}}
<div class="flex gap-2">
    <input type="text" name="password" id="{{ $id }}" minlength="8" autocomplete="new-password" required
           placeholder="Mínimo 8 caracteres" value="{{ $valor ?? '' }}"
           class="flex-1 min-w-0 border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-400
                  {{ ! empty($error) ? 'border-red-400' : '' }}">
    <button type="button" data-generar="{{ $id }}"
            class="flex-shrink-0 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-3 py-2 rounded transition">
        Generar
    </button>
</div>
@if(! empty($error))
    <p class="text-red-500 text-xs mt-1">{{ $error }}</p>
@endif

@once
    @push('scripts')
        <script>
            // Sin letras que se confundan (l, 1, O, 0): se dicta por teléfono o WhatsApp.
            document.querySelectorAll('[data-generar]').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    const letras = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
                    const azar = new Uint32Array(10);
                    crypto.getRandomValues(azar);
                    document.getElementById(boton.dataset.generar).value =
                        Array.from(azar, n => letras[n % letras.length]).join('');
                });
            });
        </script>
    @endpush
@endonce
