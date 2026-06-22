<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción <span class="text-red-500">*</span></label>
        <input type="text" name="descripcion" placeholder="Ej: Préstamo neumáticos, Tarjeta en cuotas"
               value="{{ old('descripcion', $prestamo->descripcion ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('descripcion') border-red-400 @enderror">
        @error('descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría <span class="text-red-500">*</span></label>
        <select name="categoria"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('categoria') border-red-400 @enderror">
            @foreach($categorias as $valor => $etiqueta)
                <option value="{{ $valor }}" {{ old('categoria', $prestamo->categoria ?? 'camion') === $valor ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('categoria') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Medio de pago</label>
        <select name="medio_pago_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('medio_pago_id') border-red-400 @enderror">
            <option value="">— Sin especificar —</option>
            @foreach($mediosPago as $medio)
                <option value="{{ $medio->id }}" {{ (string) old('medio_pago_id', $prestamo->medio_pago_id ?? '') === (string) $medio->id ? 'selected' : '' }}>
                    {{ $medio->nombre }}
                </option>
            @endforeach
        </select>
        @error('medio_pago_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Monto total ($) <span class="text-red-500">*</span></label>
        <input type="number" name="monto_total" id="monto_total" min="0" step="0.01" placeholder="1200000.00"
               value="{{ old('monto_total', $prestamo->monto_total ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('monto_total') border-red-400 @enderror">
        @error('monto_total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad de cuotas <span class="text-red-500">*</span></label>
        <input type="number" name="cantidad_cuotas" id="cantidad_cuotas" min="1" max="120" placeholder="12"
               value="{{ old('cantidad_cuotas', $prestamo->cantidad_cuotas ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('cantidad_cuotas') border-red-400 @enderror">
        @error('cantidad_cuotas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Valor de cuota ($) <span class="text-red-500">*</span></label>
        <input type="number" name="valor_cuota" id="valor_cuota" min="0" step="0.01" placeholder="100000.00"
               value="{{ old('valor_cuota', $prestamo->valor_cuota ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('valor_cuota') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Se sugiere monto ÷ cuotas; podés ajustarlo.</p>
        @error('valor_cuota') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Día de vencimiento mensual <span class="text-red-500">*</span></label>
        <input type="number" name="dia_vencimiento" min="1" max="31" placeholder="10"
               value="{{ old('dia_vencimiento', $prestamo->dia_vencimiento ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('dia_vencimiento') border-red-400 @enderror">
        @error('dia_vencimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de la primera cuota <span class="text-red-500">*</span></label>
        <input type="date" name="fecha_primera_cuota"
               value="{{ old('fecha_primera_cuota', isset($prestamo) && $prestamo->fecha_primera_cuota ? $prestamo->fecha_primera_cuota->format('Y-m-d') : '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha_primera_cuota') border-red-400 @enderror">
        @error('fecha_primera_cuota') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="2" placeholder="Notas del préstamo..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $prestamo->observaciones ?? '') }}</textarea>
        @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

@isset($prestamo)
    <p class="mt-3 text-xs text-orange-600">
        Al actualizar, las cuotas <strong>ya pagadas</strong> se conservan y solo se recalculan las impagas.
    </p>
@endisset

<script>
    // Sugerir valor de cuota = monto_total / cantidad_cuotas (solo si el usuario no lo tocó)
    (function () {
        const monto = document.getElementById('monto_total');
        const cant  = document.getElementById('cantidad_cuotas');
        const valor = document.getElementById('valor_cuota');
        if (!monto || !cant || !valor) return;

        let tocado = valor.value !== '';
        valor.addEventListener('input', () => { tocado = true; });

        function sugerir() {
            if (tocado) return;
            const m = parseFloat(monto.value) || 0;
            const c = parseInt(cant.value, 10) || 0;
            if (m > 0 && c > 0) valor.value = (m / c).toFixed(2);
        }
        monto.addEventListener('input', sugerir);
        cant.addEventListener('input', sugerir);
    })();
</script>
