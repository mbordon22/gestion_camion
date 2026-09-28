@php $renombrable = isset($producto) && $producto->viajes()->exists(); @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" id="nombre" maxlength="60" placeholder="Ej: cereal, hacienda, azúcar"
               value="{{ old('nombre', $producto->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        @if($renombrable)
            <p class="text-xs text-gray-400 mt-1">Si le cambiás el nombre, se corrige también en los viajes y las tarifas que lo usan.</p>
        @endif
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Texto libre con las unidades de siempre como sugerencia, igual que en el viaje. --}}
    <div>
        <label for="unidad" class="block text-sm font-medium text-gray-700 mb-1">Se mide en</label>
        <input type="text" name="unidad" id="unidad" list="producto-unidades" maxlength="20" placeholder="Ej: toneladas" autocomplete="off"
               value="{{ old('unidad', $producto->unidad ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('unidad') border-red-400 @enderror">
        <datalist id="producto-unidades">
            @foreach($unidades as $valor => $etiqueta)
                <option value="{{ $valor }}">{{ $etiqueta }}</option>
            @endforeach
        </datalist>
        <p class="text-xs text-gray-400 mt-1">Opcional. Al elegir el producto en un viaje, la unidad se completa sola.</p>
        @error('unidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $producto->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (aparece al cargar viajes)
        </label>
    </div>
</div>
