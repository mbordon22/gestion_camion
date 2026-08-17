{{--
    Selector de camión, compartido por viajes, combustible y mantenimiento.
    Espera:
      $camiones     -> colección de Camion activos
      $camionActual -> id del camión seleccionado (o null)

    Con un solo camión no hay nada que elegir: se manda oculto y se muestra
    cuál es, para ahorrar un clic en cada carga.
--}}
@php
    $camionUnico = $camiones->count() === 1 ? $camiones->first() : null;
    $ocultarSelector = $camionUnico
        && (! $camionActual || (string) $camionActual === (string) $camionUnico->id);
@endphp

@if($ocultarSelector)
    <input type="hidden" name="camion_id" value="{{ $camionUnico->id }}">
    <div class="sm:col-span-2 text-sm text-gray-500">
        Camión: <span class="font-medium text-gray-700">{{ $camionUnico->nombre() }}</span>
    </div>
@else
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Camión <span class="text-red-500">*</span></label>
        <select name="camion_id"
                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                       @error('camion_id') border-red-400 @enderror">
            <option value="">— Seleccionar —</option>
            @foreach($camiones as $camion)
                <option value="{{ $camion->id }}"
                        {{ (string) old('camion_id', $camionActual) === (string) $camion->id ? 'selected' : '' }}>
                    {{ $camion->nombre() }}
                </option>
            @endforeach
        </select>
        @error('camion_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
@endif
