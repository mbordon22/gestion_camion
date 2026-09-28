<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">N° de orden</label>
    <input type="text" name="nro_orden" id="nro_orden" maxlength="30" placeholder="Ej: 10110" autocomplete="off"
           value="{{ old('nro_orden', $viaje->nro_orden ?? '') }}"
           data-url="{{ route('viajes.buscar-orden') }}"
           data-excluir="{{ $viaje->id ?? '' }}"
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                  @error('nro_orden') border-red-400 @enderror">
    <p class="text-xs text-gray-400 mt-1">Si te dieron una orden de carga o remito.</p>
    @error('nro_orden') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    <div id="orden-repetida" role="status" aria-live="polite"
         class="hidden mt-2 rounded border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900"></div>
</div>
