<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

    @include('partials._camion_select', ['camiones' => $camiones, 'camionActual' => $mantenimiento->camion_id ?? null])

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
        <input type="date" name="fecha"
               value="{{ old('fecha', isset($mantenimiento) ? $mantenimiento->fecha->format('Y-m-d') : date('Y-m-d')) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha') border-red-400 @enderror">
        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
        <select name="tipo"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('tipo') border-red-400 @enderror">
            <option value="">Seleccioná un tipo...</option>
            @foreach($tipos as $valor => $etiqueta)
                <option value="{{ $valor }}"
                    {{ old('tipo', $mantenimiento->tipo ?? '') === $valor ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        @error('tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Monto ($) <span class="text-red-500">*</span></label>
        <input type="number" name="monto" min="0" step="0.01" placeholder="85000.00"
               value="{{ old('monto', $mantenimiento->monto ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('monto') border-red-400 @enderror">
        @error('monto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Km actuales</label>
        <input type="number" name="km_actuales" min="0" placeholder="124000"
               value="{{ old('km_actuales', $mantenimiento->km_actuales ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('km_actuales') border-red-400 @enderror">
        @error('km_actuales') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Próximo service (km)
            <span class="text-xs text-orange-600 font-normal ml-1">— genera alerta en el listado</span>
        </label>
        <input type="number" name="proximo_service" min="0" placeholder="134000"
               value="{{ old('proximo_service', $mantenimiento->proximo_service ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('proximo_service') border-red-400 @enderror">
        @error('proximo_service') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Detalle</label>
        <textarea name="detalle" rows="3" placeholder="Descripción del trabajo realizado..."
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                         @error('detalle') border-red-400 @enderror">{{ old('detalle', $mantenimiento->detalle ?? '') }}</textarea>
        @error('detalle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @include('partials._medio_pago_fields', [
        'mediosPago'  => $mediosPago,
        'medioActual' => $mantenimiento->medio_pago_id ?? null,
        'vencActual'  => isset($mantenimiento) && $mantenimiento->fecha_vencimiento ? $mantenimiento->fecha_vencimiento->format('Y-m-d') : '',
    ])
</div>
