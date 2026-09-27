<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" maxlength="100" placeholder="Churqui"
               value="{{ old('nombre', $destino->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
        <select name="cliente_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('cliente_id') border-red-400 @enderror">
            <option value="">Para cualquier cliente</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}"
                        {{ (string) old('cliente_id', $destino->cliente_id ?? '') === (string) $cliente->id ? 'selected' : '' }}>
                    {{ $cliente->nombre }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-400 mt-1">Opcional. Dos clientes pueden tener el mismo destino a distinta distancia.</p>
        @error('cliente_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
        <input type="text" name="origen" maxlength="100" placeholder="Ingenio La Corona"
               value="{{ old('origen', $destino->origen ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('origen') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Desde dónde se miden los km. Se completa solo en el viaje.</p>
        @error('origen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Distancia (km)</label>
        <input type="number" name="km" min="0" max="5000" placeholder="12"
               value="{{ old('km', $destino->km ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('km') border-red-400 @enderror">
        @error('km') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2 flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $destino->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (aparece al cargar viajes)
        </label>
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea name="notas" rows="3" placeholder="Cómo se entra, horarios, con quién hay que hablar..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('notas') border-red-400 @enderror">{{ old('notas', $destino->notas ?? '') }}</textarea>
        @error('notas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>
