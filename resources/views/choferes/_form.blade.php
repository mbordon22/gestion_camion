@php
    $modalidadActual = old('modalidad', $chofer->modalidad ?? '');
    $valorActual = old('valor', isset($chofer) && $chofer->valor !== null
        ? rtrim(rtrim(number_format((float) $chofer->valor, 2, '.', ''), '0'), '.')
        : '');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" maxlength="100" placeholder="Rivadeneira"
               value="{{ old('nombre', $chofer->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Como figura en el ticket de balanza.</p>
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $chofer->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (aparece al cargar viajes)
        </label>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
        <input type="text" name="dni" maxlength="10" placeholder="12.345.678" inputmode="numeric"
               value="{{ old('dni', $chofer->dni ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('dni') border-red-400 @enderror">
        @error('dni') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="tel" name="telefono" maxlength="30" placeholder="381 555-1234"
               value="{{ old('telefono', $chofer->telefono ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('telefono') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Con código de área, sin el 15: sirve para mandarle la liquidación por WhatsApp.</p>
        @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{--
        A comisión se lleva una parte de cada viaje; si no, sus viajes no
        descuentan nada. Sólo si la cuenta usa comisiones (Configuración).
    --}}
    @usa('comisiones')
    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cómo cobra</label>
            <select name="modalidad" id="modalidad"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400
                           @error('modalidad') border-red-400 @enderror">
                <option value="">Sin comisión (sueldo, o maneja el dueño)</option>
                @foreach(\App\Models\Chofer::$modalidades as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ $modalidadActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
            @error('modalidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div id="bloque-valor" class="{{ $modalidadActual ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span id="valor-etiqueta">{{ $modalidadActual === 'fijo_viaje' ? 'Monto por viaje ($)' : 'Porcentaje (%)' }}</span>
                <span class="text-red-500">*</span>
            </label>
            <input type="number" name="valor" id="valor" min="0" step="0.01"
                   placeholder="{{ $modalidadActual === 'fijo_viaje' ? '20000' : '15' }}"
                   value="{{ $valorActual }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('valor') border-red-400 @enderror">
            @error('valor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <p class="sm:col-span-2 text-xs text-gray-500">
            El porcentaje se calcula sobre el total bruto del viaje. Si cambiás el acuerdo, vale para los viajes que cargues
            de acá en adelante: los que ya están cargados conservan lo que se grabó.
        </p>
    </div>
    @endusa

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3" placeholder="Vencimiento del registro, licencia de cargas peligrosas, lo que quieras recordar..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('notas') border-red-400 @enderror">{{ old('notas', $chofer->notas ?? '') }}</textarea>
        @error('notas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<script>
(function () {
    const modalidad = document.getElementById('modalidad');
    if (! modalidad) return;
    const bloque    = document.getElementById('bloque-valor');
    const etiqueta  = document.getElementById('valor-etiqueta');
    const valor     = document.getElementById('valor');

    modalidad.addEventListener('change', () => {
        const porcentaje = modalidad.value !== 'fijo_viaje';
        bloque.classList.toggle('hidden', modalidad.value === '');
        etiqueta.textContent = porcentaje ? 'Porcentaje (%)' : 'Monto por viaje ($)';
        valor.placeholder = porcentaje ? '15' : '20000';
    });
})();
</script>
