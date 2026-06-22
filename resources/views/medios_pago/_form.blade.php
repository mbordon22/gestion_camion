<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" placeholder="Ej: Visa Banco Nación, Mercado Crédito"
               value="{{ old('nombre', $medioPago->nombre ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nombre') border-red-400 @enderror">
        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
        <select name="tipo" id="tipo"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('tipo') border-red-400 @enderror">
            @foreach($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ old('tipo', $medioPago->tipo ?? '') === $valor ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-end pb-1">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1"
                   {{ old('activo', $medioPago->activo ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
            Activo (disponible para usar)
        </label>
    </div>

    {{-- Campos solo para crédito --}}
    <div id="campos-credito" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="sm:col-span-2 text-xs text-blue-700">
            <strong>Opcional.</strong> Si cargás el día de cierre y de vencimiento, el sistema te
            sugiere sola la fecha de pago al cargar cada gasto. Si lo dejás vacío (ej. Mercado Crédito),
            ponés la fecha de pago a mano en cada gasto.
        </p>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Día de cierre</label>
            <input type="number" name="dia_cierre" min="1" max="31" placeholder="25"
                   value="{{ old('dia_cierre', $medioPago->dia_cierre ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('dia_cierre') border-red-400 @enderror">
            @error('dia_cierre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Día de vencimiento</label>
            <input type="number" name="dia_vencimiento" min="1" max="31" placeholder="10"
                   value="{{ old('dia_vencimiento', $medioPago->dia_vencimiento ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                          @error('dia_vencimiento') border-red-400 @enderror">
            @error('dia_vencimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

<script>
    function toggleCamposCredito() {
        const tipo = document.getElementById('tipo').value;
        document.getElementById('campos-credito').style.display = (tipo === 'credito') ? '' : 'none';
    }
    document.getElementById('tipo').addEventListener('change', toggleCamposCredito);
    toggleCamposCredito();
</script>
