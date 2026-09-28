{{--
    Quien maneja. Mismo criterio que el cliente: se puede crear al vuelo.
    Cada opción lleva cómo cobra, para que el aviso del total haga la
    cuenta; igual que con el equipo, en un viaje ya cargado con este chofer
    vale lo que se grabó (Chofer::comisionDe).
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Chofer</label>
    <select name="chofer_id" id="chofer_id"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                   @error('chofer_id') border-red-400 @enderror">
        <option value="">Sin chofer</option>
        @foreach($choferes as $opcion)
            @php
                $grabado = isset($viaje) && $viaje->exists && $viaje->chofer_id === $opcion->id;
                if ($grabado && $viaje->liquidacion_id) {
                    // Ya se le pagó: el monto no se mueve aunque cambie el total.
                    $modalidadChofer = $viaje->comision_monto !== null ? 'fijo_viaje' : '';
                    $valorChofer = $viaje->comision_monto;
                } else {
                    $modalidadChofer = $opcion->modalidad;
                    $valorChofer = $opcion->esPorcentaje()
                        ? ($grabado && $viaje->comision_porcentaje !== null ? $viaje->comision_porcentaje : $opcion->valor)
                        : ($grabado && $viaje->comision_monto !== null ? $viaje->comision_monto : $opcion->valor);
                }
            @endphp
            <option value="{{ $opcion->id }}"
                    data-modalidad="{{ $modalidadChofer }}"
                    data-valor="{{ $valorChofer }}"
                    {{ (string) $choferActual === (string) $opcion->id ? 'selected' : '' }}>{{ $opcion->nombre }}</option>
        @endforeach
        <option value="nuevo" {{ $choferEsNuevo ? 'selected' : '' }}>+ Nuevo chofer…</option>
    </select>
    <input type="text" name="chofer_nuevo" id="chofer_nuevo" maxlength="100" placeholder="Nombre del chofer nuevo"
           value="{{ old('chofer_nuevo') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 text-sm mt-2 focus:outline-none focus:ring-2 focus:ring-blue-400
                  {{ $choferEsNuevo ? '' : 'hidden' }} @error('chofer_nuevo') border-red-400 @enderror">
    @error('chofer_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    @error('chofer_nuevo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
