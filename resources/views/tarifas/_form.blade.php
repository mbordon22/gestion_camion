<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente <span class="text-red-500">*</span></label>
        <select name="cliente_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('cliente_id') border-red-400 @enderror">
            <option value="">— Seleccionar —</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}"
                        {{ (string) old('cliente_id', $tarifa->cliente_id ?? '') === (string) $cliente->id ? 'selected' : '' }}>
                    {{ $cliente->nombre }}
                </option>
            @endforeach
        </select>
        @error('cliente_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
        <input type="text" name="producto" list="tarifa-productos" maxlength="60" placeholder="Vinaza" autocomplete="off"
               value="{{ old('producto', $tarifa->producto ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('producto') border-red-400 @enderror">
        <datalist id="tarifa-productos">
            @foreach($productos as $sugerencia)
                <option value="{{ $sugerencia }}"></option>
            @endforeach
        </datalist>
        <p class="text-xs text-gray-400 mt-1">Vacío = vale para cualquier carga de ese cliente.</p>
        @error('producto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2 grid grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde (km) <span class="text-red-500">*</span></label>
            <input type="number" name="km_desde" min="0" max="5000" placeholder="0"
                   value="{{ old('km_desde', $tarifa->km_desde ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('km_desde') border-red-400 @enderror">
            @error('km_desde') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta (km) <span class="text-red-500">*</span></label>
            <input type="number" name="km_hasta" min="0" max="5000" placeholder="8"
                   value="{{ old('km_hasta', $tarifa->km_hasta ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('km_hasta') border-red-400 @enderror">
            @error('km_hasta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Importe ($) <span class="text-red-500">*</span></label>
        <input type="number" name="importe" min="0" step="0.01" placeholder="8500.00"
               value="{{ old('importe', $tarifa->importe ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('importe') border-red-400 @enderror">
        @error('importe') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Por cada <span class="text-red-500">*</span></label>
        <select name="unidad"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('unidad') border-red-400 @enderror">
            @foreach($unidades as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ old('unidad', $tarifa->unidad ?? 'toneladas') === $valor ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">El viaje tiene que cargarse en esta misma unidad.</p>
        @error('unidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Rige desde <span class="text-red-500">*</span></label>
        <input type="date" name="vigente_desde"
               value="{{ old('vigente_desde', isset($tarifa) ? $tarifa->vigente_desde->format('Y-m-d') : date('Y-m-d')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('vigente_desde') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">
            Cuando te actualicen el precio, cargá una tarifa nueva con la fecha del aumento en vez de editar ésta:
            así los viajes viejos siguen mostrando con qué precio se cobraron.
        </p>
        @error('vigente_desde') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="2" placeholder="Cómo te lo pasaron, quién lo autorizó..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('notas') border-red-400 @enderror">{{ old('notas', $tarifa->notas ?? '') }}</textarea>
        @error('notas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
