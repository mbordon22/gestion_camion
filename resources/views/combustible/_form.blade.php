<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $combustible->camion_id ?? null])

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
        <input type="date" name="fecha"
               value="{{ old('fecha', isset($combustible) ? $combustible->fecha->format('Y-m-d') : date('Y-m-d')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha') border-red-400 @enderror">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar</label>
        <input type="text" name="lugar" placeholder="YPF Ruta 9"
               value="{{ old('lugar', $combustible->lugar ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('lugar') border-red-400 @enderror">
        @error('lugar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Litros <span class="text-red-500">*</span></label>
        <input type="number" name="litros" id="litros" min="0" step="0.01" placeholder="300.00"
               value="{{ old('litros', $combustible->litros ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('litros') border-red-400 @enderror">
        @error('litros') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio por litro ($) <span class="text-red-500">*</span></label>
        <input type="number" name="precio_litro" id="precio_litro" min="0" step="0.01" placeholder="1050.00"
               value="{{ old('precio_litro', $combustible->precio_litro ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('precio_litro') border-red-400 @enderror">
        @error('precio_litro') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Total ($)</label>
        <div class="relative">
            <input type="number" name="total" id="total" min="0" step="0.01" readonly
                   value="{{ old('total', $combustible->total ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-orange-50 font-semibold text-orange-800
                          focus:outline-none focus:ring-2 focus:ring-orange-400 cursor-not-allowed">
            <span class="absolute right-3 top-2 text-xs text-gray-400">auto-calculado</span>
        </div>
        @error('total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Km odómetro</label>
        <input type="number" name="km_odometro" min="0" placeholder="125000"
               value="{{ old('km_odometro', $combustible->km_odometro ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('km_odometro') border-red-400 @enderror">
        @error('km_odometro') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @include('partials._medio_pago_fields', [
        'mediosPago'  => $mediosPago,
        'medioActual' => $combustible->medio_pago_id ?? null,
        'vencActual'  => isset($combustible) && $combustible->fecha_vencimiento ? $combustible->fecha_vencimiento->format('Y-m-d') : '',
    ])
</div>

<script>
    function calcularTotal() {
        const litros = parseFloat(document.getElementById('litros').value) || 0;
        const precio = parseFloat(document.getElementById('precio_litro').value) || 0;
        document.getElementById('total').value = (litros * precio).toFixed(2);
    }
    document.getElementById('litros').addEventListener('input', calcularTotal);
    document.getElementById('precio_litro').addEventListener('input', calcularTotal);
</script>
