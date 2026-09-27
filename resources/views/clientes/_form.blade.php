<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" maxlength="100" placeholder="Control Union"
               value="{{ old('nombre', $cliente->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $cliente->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (aparece al cargar viajes)
        </label>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">CUIT</label>
        <input type="text" name="cuit" maxlength="13" placeholder="30-12345678-9" inputmode="numeric"
               value="{{ old('cuit', $cliente->cuit ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('cuit') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Opcional, para cuando le factures.</p>
        @error('cuit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
        <input type="tel" name="telefono" maxlength="30" placeholder="381 555-1234"
               value="{{ old('telefono', $cliente->telefono ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('telefono') border-red-400 @enderror">
        @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3" placeholder="Contacto, condiciones de pago, lo que quieras recordar..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('notas') border-red-400 @enderror">{{ old('notas', $cliente->notas ?? '') }}</textarea>
        @error('notas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
