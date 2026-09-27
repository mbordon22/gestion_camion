{{--
    Campos de medio de pago + fecha de pago, compartidos por combustible y mantenimiento.
    Espera:
      $mediosPago   -> colección de MedioPago activos
      $medioActual  -> id del medio seleccionado (o null)
      $vencActual   -> fecha de pago actual en formato Y-m-d (o '')
    Requiere que el formulario tenga un input con name="fecha" (type date).
--}}
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Medio de pago</label>
    <select name="medio_pago_id" id="medio_pago_id"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                   @error('medio_pago_id') border-red-400 @enderror">
        <option value="">— Sin especificar (contado) —</option>
        @foreach($mediosPago as $medio)
            <option value="{{ $medio->id }}"
                    data-tipo="{{ $medio->tipo }}"
                    data-cierre="{{ $medio->dia_cierre }}"
                    data-venc="{{ $medio->dia_vencimiento }}"
                    {{ (string) old('medio_pago_id', $medioActual) === (string) $medio->id ? 'selected' : '' }}>
                {{ $medio->nombre }}@switch($medio->tipo)@case('credito') (crédito)@break @case('descuento') (lo descuenta el cliente)@break @endswitch
            </option>
        @endforeach
    </select>
    @error('medio_pago_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <div id="bloque-fecha-pago">
        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de pago</label>
        <input type="date" name="fecha_vencimiento" id="fecha_vencimiento"
               value="{{ old('fecha_vencimiento', $vencActual) }}"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                      @error('fecha_vencimiento') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">Cuándo se paga este gasto. Si la tarjeta tiene cierre/vencimiento, se sugiere sola; si no, ponela vos.</p>
        @error('fecha_vencimiento') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Un gasto descontado no se paga nunca: no hay fecha de pago que poner. --}}
    <p id="aviso-descuento" class="hidden rounded border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-900">
        Este gasto no se paga: el cliente te lo descuenta de la factura. Suma como gasto en
        los reportes, pero no aparece en Pagos.
    </p>
</div>

<script>
(function () {
    const selMedio = document.getElementById('medio_pago_id');
    const inpVenc  = document.getElementById('fecha_vencimiento');
    const inpFecha = document.querySelector('input[name="fecha"]');
    if (!selMedio || !inpVenc || !inpFecha) return;

    // Si el usuario edita la fecha de pago a mano, dejamos de autocalcular.
    let editadoManual = inpVenc.value !== '';
    inpVenc.addEventListener('input', () => { editadoManual = true; });

    // Devuelve la fecha de pago sugerida, o null si no se puede deducir (debe ponerla el usuario).
    function sugerirFechaPago(fechaGasto, tipo, cierre, venc) {
        if (!fechaGasto) return null;

        if (tipo === 'credito') {
            if (!cierre || !venc) return null; // crédito sin cierre/venc -> manual
            const [y, m, d] = fechaGasto.split('-').map(Number);
            let year = y, month = m; // month 1-12
            if (d > cierre) { month += 1; if (month > 12) { month = 1; year += 1; } }
            let vy = year, vm = month;
            if (venc <= cierre) { vm += 1; if (vm > 12) { vm = 1; vy += 1; } }
            const daysInMonth = new Date(vy, vm, 0).getDate();
            const day = Math.min(venc, daysInMonth);
            return `${vy}-${String(vm).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        }

        // efectivo / débito / transferencia / sin medio -> se paga el mismo día (contado)
        return fechaGasto;
    }

    const bloqueFecha   = document.getElementById('bloque-fecha-pago');
    const avisoDescuento = document.getElementById('aviso-descuento');

    function aplicarDescuento(tipo) {
        const esDescuento = tipo === 'descuento';
        bloqueFecha.classList.toggle('hidden', esDescuento);
        avisoDescuento.classList.toggle('hidden', ! esDescuento);
    }

    function recalcular() {
        const opt = selMedio.options[selMedio.selectedIndex];
        const tipoActual = opt ? opt.getAttribute('data-tipo') : null;
        aplicarDescuento(tipoActual);

        if (editadoManual) return;
        const tipo   = tipoActual;
        const cierre = opt ? parseInt(opt.getAttribute('data-cierre'), 10) : null;
        const venc   = opt ? parseInt(opt.getAttribute('data-venc'), 10) : null;
        const sugerida = sugerirFechaPago(inpFecha.value, tipo, cierre, venc);
        inpVenc.value = sugerida === null ? '' : sugerida;
    }

    selMedio.addEventListener('change', () => { editadoManual = false; recalcular(); });
    inpFecha.addEventListener('input', recalcular);

    // Estado inicial: al editar un gasto ya descontado, el campo ya viene oculto.
    aplicarDescuento(selMedio.options[selMedio.selectedIndex]?.getAttribute('data-tipo'));
})();
</script>
