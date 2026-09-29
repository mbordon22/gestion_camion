{{--
    Qué funciones avanzadas usa una cuenta (Cuenta::FUNCIONES), como
    interruptores. Lo usan la Configuración de cada cuenta y el panel del
    administrador. Espera $elegidas: las claves prendidas.
--}}
@php $elegidas = old('funciones', $elegidas ?? []); @endphp
<input type="hidden" name="funciones[]" value="">
<ul class="divide-y divide-gray-100">
    @foreach(\App\Models\Cuenta::FUNCIONES as $clave => $funcion)
        <li>
            <label class="flex items-start justify-between gap-4 py-4 cursor-pointer">
                <span>
                    <span class="block text-sm font-medium text-gray-800">{{ $funcion['titulo'] }}</span>
                    <span class="block text-xs text-gray-500 mt-0.5">{{ $funcion['descripcion'] }}</span>
                </span>
                <input type="checkbox" name="funciones[]" value="{{ $clave }}" class="sr-only peer"
                       {{ in_array($clave, $elegidas, true) ? 'checked' : '' }}>
                <span class="relative flex-shrink-0 mt-0.5 w-11 h-6 bg-gray-300 rounded-full transition-colors
                             peer-checked:bg-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-400
                             after:content-[''] after:absolute after:top-0.5 after:left-0.5
                             after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                             peer-checked:after:translate-x-5"></span>
            </label>
        </li>
    @endforeach
</ul>
@error('funciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
