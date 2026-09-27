@php
    $alquiladoActual = (bool) old('alquilado', $equipo->alquilado ?? false);
    $modalidadActual = old('modalidad', $equipo->modalidad ?? 'porcentaje');
    $valorActual = old('valor', isset($equipo) && $equipo->valor !== null
        ? rtrim(rtrim(number_format((float) $equipo->valor, 2, '.', ''), '0'), '.')
        : '');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" maxlength="100" placeholder="Cisterna vinaza"
               value="{{ old('nombre', $equipo->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Como lo vas a reconocer al cargar un viaje.</p>
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
        <input type="text" name="tipo" list="tipos-equipo" maxlength="40" placeholder="Cisterna" autocomplete="off"
               value="{{ old('tipo', $equipo->tipo ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('tipo') border-red-400 @enderror">
        <datalist id="tipos-equipo">
            @foreach(\App\Models\Equipo::$tipos as $tipo)
                <option value="{{ $tipo }}"></option>
            @endforeach
        </datalist>
        @error('tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Patente</label>
        <input type="text" name="patente" maxlength="15" placeholder="AB123CD"
               value="{{ old('patente', $equipo->patente ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('patente') border-red-400 @enderror">
        @error('patente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Camión habitual</label>
        <select name="camion_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('camion_id') border-red-400 @enderror">
            <option value="">Ninguno en particular</option>
            @foreach($camiones as $camion)
                <option value="{{ $camion->id }}" {{ (string) old('camion_id', $equipo->camion_id ?? '') === (string) $camion->id ? 'selected' : '' }}>
                    {{ $camion->nombre() }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Al cargar un viaje con este camión se propone este equipo.</p>
        @error('camion_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Propio o alquilado: sólo el alquilado tiene acuerdo con el dueño. --}}
    <div class="sm:col-span-2">
        <span class="block text-sm font-medium text-gray-700 mb-2">¿De quién es?</span>
        <div class="flex flex-wrap gap-6">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="radio" name="alquilado" value="0" class="equipo-alquilado text-blue-600 focus:ring-blue-400"
                       {{ $alquiladoActual ? '' : 'checked' }}>
                Propio
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="radio" name="alquilado" value="1" class="equipo-alquilado text-blue-600 focus:ring-blue-400"
                       {{ $alquiladoActual ? 'checked' : '' }}>
                Alquilado
            </label>
        </div>
    </div>

    <div id="bloque-alquiler" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-5 rounded-lg border border-amber-200 bg-amber-50 p-4
                                    {{ $alquiladoActual ? '' : 'hidden' }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dueño</label>
            <input type="text" name="propietario" maxlength="100" placeholder="A quién se lo alquilás"
                   value="{{ old('propietario', $equipo->propietario ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('propietario') border-red-400 @enderror">
            @error('propietario') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cómo cobra <span class="text-red-500">*</span></label>
            <select name="modalidad" id="modalidad"
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400
                           @error('modalidad') border-red-400 @enderror">
                @foreach(\App\Models\Equipo::$modalidades as $valor => $etiqueta)
                    <option value="{{ $valor }}" {{ $modalidadActual === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                @endforeach
            </select>
            @error('modalidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                <span id="valor-etiqueta">{{ $modalidadActual === 'porcentaje' ? 'Porcentaje (%)' : 'Monto por viaje ($)' }}</span>
                <span class="text-red-500">*</span>
            </label>
            <input type="number" name="valor" id="valor" min="0" step="0.01" placeholder="{{ $modalidadActual === 'porcentaje' ? '25' : '20000' }}"
                   value="{{ $valorActual }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('valor') border-red-400 @enderror">
            @error('valor') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <p class="sm:col-span-3 text-xs text-amber-800">
            El porcentaje se calcula sobre el total bruto del viaje. Si cambiás el acuerdo, vale para los viajes que cargues
            de acá en adelante: los que ya están cargados conservan lo que se grabó.
        </p>
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $equipo->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (aparece al cargar viajes)
        </label>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3" placeholder="Desde cuándo lo alquilás, cómo se arregla el pago, lo que quieras recordar..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('notas') border-red-400 @enderror">{{ old('notas', $equipo->notas ?? '') }}</textarea>
        @error('notas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<script>
(function () {
    const bloque    = document.getElementById('bloque-alquiler');
    const modalidad = document.getElementById('modalidad');
    const etiqueta  = document.getElementById('valor-etiqueta');
    const valor     = document.getElementById('valor');

    document.querySelectorAll('.equipo-alquilado').forEach(radio => radio.addEventListener('change', () => {
        bloque.classList.toggle('hidden', radio.value !== '1' || ! radio.checked);
    }));

    modalidad.addEventListener('change', () => {
        const porcentaje = modalidad.value === 'porcentaje';
        etiqueta.textContent = porcentaje ? 'Porcentaje (%)' : 'Monto por viaje ($)';
        valor.placeholder = porcentaje ? '25' : '20000';
    });
})();
</script>
