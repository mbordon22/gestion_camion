<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Patente <span class="text-red-500">*</span></label>
        <input type="text" name="patente" placeholder="AB123CD"
               value="{{ old('patente', $camion->patente ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('patente') border-red-400 @enderror">
        @error('patente') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $camion->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (disponible para usar)
        </label>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
        <input type="text" name="marca" placeholder="Scania"
               value="{{ old('marca', $camion->marca ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('marca') border-red-400 @enderror">
        @error('marca') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
        <input type="text" name="modelo" placeholder="R 450"
               value="{{ old('modelo', $camion->modelo ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('modelo') border-red-400 @enderror">
        @error('modelo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
        <input type="number" name="anio" min="1950" max="{{ date('Y') + 1 }}" placeholder="2020"
               value="{{ old('anio', $camion->anio ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('anio') border-red-400 @enderror">
        @error('anio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="consumo_cada_100km" class="block text-sm font-medium text-gray-700 mb-1">Consumo (litros cada 100 km)</label>
        <input type="text" inputmode="decimal" name="consumo_cada_100km" id="consumo_cada_100km" placeholder="Ej: 35"
               value="{{ old('consumo_cada_100km', isset($camion) && $camion->consumo_cada_100km !== null ? \App\Models\Viaje::valorCampo($camion->consumo_cada_100km) : '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('consumo_cada_100km') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Opcional. Lo usa el simulador de viajes para calcular el gasoil.</p>
        @error('consumo_cada_100km') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3" placeholder="Notas sobre el camión..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $camion->observaciones ?? '') }}</textarea>
        @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
