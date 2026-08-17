<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $viaje->camion_id ?? null])

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora orden de carga <span class="text-red-500">*</span></label>
        <input type="datetime-local" name="fecha"
               value="{{ old('fecha', isset($viaje) ? $viaje->fecha->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha') border-red-400 @enderror">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de carga real</label>
        <input type="date" name="fecha_carga"
               value="{{ old('fecha_carga', isset($viaje) && $viaje->fecha_carga ? $viaje->fecha_carga->format('Y-m-d') : '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha_carga') border-red-400 @enderror">
        @error('fecha_carga') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Ingreso</label>
        <input type="text" name="nro_ingreso" placeholder="ING-001"
               value="{{ old('nro_ingreso', $viaje->nro_ingreso ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('nro_ingreso') border-red-400 @enderror">
        @error('nro_ingreso') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Ingreso</label>
        <input type="text" name="tipo_ingreso" placeholder="Ej: Azúcar blanca"
               value="{{ old('tipo_ingreso', $viaje->tipo_ingreso ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('tipo_ingreso') border-red-400 @enderror">
        @error('tipo_ingreso') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
        <input type="text" name="motivo" placeholder="Ej: Entrega programada a planta"
               value="{{ old('motivo', $viaje->motivo ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('motivo') border-red-400 @enderror">
        @error('motivo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Bolsas <span class="text-red-500">*</span></label>
        <input type="number" name="bolsas" id="bolsas" min="1" placeholder="500"
               value="{{ old('bolsas', $viaje->bolsas ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('bolsas') border-red-400 @enderror">
        @error('bolsas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Precio por bolsa ($) <span class="text-red-500">*</span></label>
        <input type="number" name="precio_bolsa" id="precio_bolsa" min="0" step="0.01" placeholder="1200.00"
               value="{{ old('precio_bolsa', $viaje->precio_bolsa ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('precio_bolsa') border-red-400 @enderror">
        @error('precio_bolsa') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Total ($)</label>
        <div class="relative">
            <input type="number" name="total" id="total" min="0" step="0.01" readonly
                   value="{{ old('total', $viaje->total ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-green-50 font-semibold text-green-800
                          focus:outline-none focus:ring-2 focus:ring-green-400 cursor-not-allowed">
            <span class="absolute right-3 top-2 text-xs text-gray-400">auto-calculado</span>
        </div>
        @error('total') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="facturado" value="0">
            <input type="checkbox" name="facturado" value="1" class="sr-only peer"
                   {{ old('facturado', $viaje->facturado ?? false) ? 'checked' : '' }}>
            <span class="relative w-11 h-6 bg-gray-300 rounded-full transition-colors
                         peer-checked:bg-green-500
                         after:content-[''] after:absolute after:top-0.5 after:left-0.5
                         after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                         peer-checked:after:translate-x-5"></span>
            <span class="text-sm font-medium text-gray-700">Viaje facturado</span>
        </label>
        <p class="text-xs text-gray-400 mt-1">Marcalo si este viaje/trabajo ya está facturado.</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Kg Netos</label>
        <input type="number" name="kg_netos" min="0" step="0.01" placeholder="25000"
               value="{{ old('kg_netos', $viaje->kg_netos ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('kg_netos') border-red-400 @enderror">
        @error('kg_netos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Destino</label>
        <input type="text" name="destino" placeholder="Tucumán"
               value="{{ old('destino', $viaje->destino ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('destino') border-red-400 @enderror">
        @error('destino') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
        <textarea name="observaciones" rows="3" placeholder="Notas del viaje..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('observaciones') border-red-400 @enderror">{{ old('observaciones', $viaje->observaciones ?? '') }}</textarea>
        @error('observaciones') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<script>
    function calcularTotal() {
        const bolsas = parseFloat(document.getElementById('bolsas').value) || 0;
        const precio = parseFloat(document.getElementById('precio_bolsa').value) || 0;
        document.getElementById('total').value = (bolsas * precio).toFixed(2);
    }
    document.getElementById('bolsas').addEventListener('input', calcularTotal);
    document.getElementById('precio_bolsa').addEventListener('input', calcularTotal);
</script>
