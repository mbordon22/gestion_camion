{{-- El puntito del radio: peer-checked sólo llega a hermanos y este span va anidado. --}}
<style>
    .modo-cobro:checked + span .modo-dot {
        border-color: #2563eb;
        border-width: 5px;
    }
</style>

@php
    $modoActual   = old('modo_cobro', $viaje->modo_cobro ?? 'cantidad');
    $unidadActual = old('unidad', $viaje->unidad ?? 'bolsas');
    $unidadEsOtra = $unidadActual && ! array_key_exists($unidadActual, \App\Models\Viaje::$unidades);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $viaje->camion_id ?? null])

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del viaje <span class="text-red-500">*</span></label>
        <input type="datetime-local" name="fecha"
               value="{{ old('fecha', isset($viaje) ? $viaje->fecha->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha') border-red-400 @enderror">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de carga</label>
        <input type="date" name="fecha_carga"
               value="{{ old('fecha_carga', isset($viaje) && $viaje->fecha_carga ? $viaje->fecha_carga->format('Y-m-d') : '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha_carga') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Opcional, si la carga fue otro día.</p>
        @error('fecha_carga') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Forma de cobro: define qué campos se piden abajo --}}
    <div class="sm:col-span-2">
        <span class="block text-sm font-medium text-gray-700 mb-2">¿Cómo cobrás este viaje? <span class="text-red-500">*</span></span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach(\App\Models\Viaje::$modosCobro as $valor => $etiqueta)
                <label class="block cursor-pointer">
                    <input type="radio" name="modo_cobro" value="{{ $valor }}" class="modo-cobro peer sr-only"
                           {{ $modoActual === $valor ? 'checked' : '' }}>
                    <span class="flex items-start gap-3 border-2 border-gray-200 rounded-lg p-4 transition
                                 hover:border-gray-300 peer-checked:border-blue-500 peer-checked:bg-blue-50">
                        <span class="modo-dot mt-0.5 w-4 h-4 flex-shrink-0 rounded-full border-2 border-gray-300 bg-white"></span>
                        <span>
                            <span class="block text-sm font-medium text-gray-800">{{ $etiqueta }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">
                                {{ $valor === 'fijo' ? 'Un precio cerrado por el flete.' : 'Bolsas, toneladas, pallets, cabezas...' }}
                            </span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('modo_cobro') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Sólo para el modo "por cantidad" --}}
    <div id="bloque-cantidad" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-5 {{ $modoActual === 'fijo' ? 'hidden' : '' }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad <span class="text-red-500">*</span></label>
            <input type="number" name="cantidad" id="cantidad" min="0" step="0.01" placeholder="800"
                   value="{{ old('cantidad', isset($viaje) && $viaje->cantidad ? rtrim(rtrim(number_format((float) $viaje->cantidad, 2, '.', ''), '0'), '.') : '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('cantidad') border-red-400 @enderror">
            @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unidad <span class="text-red-500">*</span></label>
            <select id="unidad_opcion"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                           @error('unidad') border-red-400 @enderror">
                @foreach(\App\Models\Viaje::$unidades as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ ! $unidadEsOtra && $unidadActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
                <option value="__otra__" {{ $unidadEsOtra ? 'selected' : '' }}>Otra...</option>
            </select>
            <input type="text" name="unidad" id="unidad" maxlength="20" placeholder="Ej: metros cúbicos"
                   value="{{ $unidadActual }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                          {{ $unidadEsOtra ? '' : 'hidden' }}">
            @error('unidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio por unidad ($) <span class="text-red-500">*</span></label>
            <input type="number" name="precio_unitario" id="precio_unitario" min="0" step="0.01" placeholder="280.00"
                   value="{{ old('precio_unitario', isset($viaje) && $viaje->precio_unitario ? $viaje->precio_unitario : '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('precio_unitario') border-red-400 @enderror">
            @error('precio_unitario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Total del viaje ($) <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="number" name="total" id="total" min="0" step="0.01" placeholder="150000.00"
                   value="{{ old('total', $viaje->total ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-green-50 font-semibold text-green-800
                          focus:outline-none focus:ring-2 focus:ring-green-400">
            <span id="total-hint" class="absolute right-3 top-2 text-xs text-gray-400 {{ $modoActual === 'fijo' ? 'hidden' : '' }}">auto-calculado</span>
        </div>
        @error('total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
        <input type="text" name="origen" placeholder="Tucumán"
               value="{{ old('origen', $viaje->origen ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('origen') border-red-400 @enderror">
        @error('origen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
        <input type="text" name="destino" placeholder="Salta"
               value="{{ old('destino', $viaje->destino ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('destino') border-red-400 @enderror">
        @error('destino') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Km recorridos</label>
        <input type="number" name="km_recorridos" min="0" placeholder="450"
               value="{{ old('km_recorridos', $viaje->km_recorridos ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('km_recorridos') border-red-400 @enderror">
        @error('km_recorridos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="cobrado" value="0">
            <input type="checkbox" name="cobrado" value="1" class="sr-only peer"
                   {{ old('cobrado', $viaje->cobrado ?? false) ? 'checked' : '' }}>
            <span class="relative w-11 h-6 bg-gray-300 rounded-full transition-colors
                         peer-checked:bg-green-500
                         after:content-[''] after:absolute after:top-0.5 after:left-0.5
                         after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                         peer-checked:after:translate-x-5"></span>
            <span class="text-sm font-medium text-gray-700">Ya lo cobré</span>
        </label>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3" placeholder="Notas del viaje..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $viaje->observaciones ?? '') }}</textarea>
        @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<script>
(function () {
    const bloqueCantidad = document.getElementById('bloque-cantidad');
    const inpCantidad    = document.getElementById('cantidad');
    const inpPrecio      = document.getElementById('precio_unitario');
    const inpTotal       = document.getElementById('total');
    const hintTotal      = document.getElementById('total-hint');
    const selUnidad      = document.getElementById('unidad_opcion');
    const inpUnidad      = document.getElementById('unidad');

    // El total se calcula solo cuando se cobra por cantidad; con monto fijo
    // lo escribe el usuario. (El servidor lo recalcula igual al guardar.)
    function calcularTotal() {
        // Sin datos todavía, dejamos el campo vacío en vez de mostrar 0,00.
        if (inpCantidad.value === '' && inpPrecio.value === '') {
            inpTotal.value = '';
            return;
        }
        const cantidad = parseFloat(inpCantidad.value) || 0;
        const precio   = parseFloat(inpPrecio.value) || 0;
        inpTotal.value = (cantidad * precio).toFixed(2);
    }

    function aplicarModo() {
        const modo = document.querySelector('input[name="modo_cobro"]:checked')?.value || 'cantidad';
        const porCantidad = modo === 'cantidad';

        bloqueCantidad.classList.toggle('hidden', ! porCantidad);
        hintTotal.classList.toggle('hidden', ! porCantidad);
        inpTotal.readOnly = porCantidad;
        inpTotal.classList.toggle('cursor-not-allowed', porCantidad);

        if (porCantidad) calcularTotal();
    }

    function aplicarUnidad() {
        const esOtra = selUnidad.value === '__otra__';
        inpUnidad.classList.toggle('hidden', ! esOtra);

        if (esOtra) {
            inpUnidad.focus();
        } else {
            inpUnidad.value = selUnidad.value;
        }
    }

    document.querySelectorAll('.modo-cobro').forEach(r => r.addEventListener('change', aplicarModo));
    inpCantidad.addEventListener('input', calcularTotal);
    inpPrecio.addEventListener('input', calcularTotal);
    selUnidad.addEventListener('change', aplicarUnidad);

    aplicarModo();
})();
</script>
