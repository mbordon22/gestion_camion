@extends('layouts.app')

@section('title', 'Liquidación del chofer')

@section('content')
@php
    $comisiones = $viajes->sum('comision_monto');
    $adelantos  = $movimientos->where('tipo', 'adelanto')->sum('monto');
    $gastos     = $movimientos->where('tipo', 'gasto')->sum('monto');
    $saldo      = $comisiones - $adelantos + $gastos;
    $hayPendientes = $viajes->isNotEmpty() || $movimientos->isNotEmpty();
    $tipoActual = old('tipo', 'adelanto');
@endphp

<div class="max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('choferes.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Liquidación · {{ $chofer->nombre }}</h1>
    </div>
    <p class="text-sm text-gray-500 mb-6">
        {{ $chofer->condicion() }}
        @unless($chofer->cobraPorViaje())
            · <a href="{{ route('choferes.edit', $chofer) }}" class="text-blue-600 hover:underline">cargá cómo cobra</a> para que sus viajes lleven comisión.
        @endunless
    </p>

    {{-- Cargar un adelanto o un gasto que pagó él. --}}
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Cargar adelanto o gasto</h2>
        </div>
        <form method="POST" action="{{ route('choferes.movimientos.store', $chofer) }}" class="px-5 py-4 flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <span class="block text-xs font-medium text-gray-600 mb-1">Qué es</span>
                <div class="flex gap-4 py-2">
                    @foreach(\App\Models\ChoferMovimiento::$tipos as $valor => $etiqueta)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="radio" name="tipo" value="{{ $valor }}" class="tipo-movimiento text-blue-600 focus:ring-blue-400"
                                   {{ $tipoActual === $valor ? 'checked' : '' }}>
                            {{ $etiqueta }}
                        </label>
                    @endforeach
                </div>
                @error('tipo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha</label>
                <input type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}"
                       class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                              @error('fecha') border-red-400 @enderror">
                @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Monto ($)</label>
                <input type="number" name="monto" min="0" step="0.01" placeholder="50000" value="{{ old('monto') }}"
                       class="w-36 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                              @error('monto') border-red-400 @enderror">
                @error('monto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Concepto</label>
                <input type="text" name="concepto" maxlength="150" value="{{ old('concepto') }}"
                       placeholder="{{ $tipoActual === 'gasto' ? 'Peaje, gomería, comida en ruta…' : 'A cuenta de la quincena' }}"
                       id="concepto"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                              @error('concepto') border-red-400 @enderror">
                @error('concepto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            {{-- El gasto cuenta en la rentabilidad del camión: sólo se pregunta cuál si hay más de uno. --}}
            @if($camiones->count() > 1)
                <div id="bloque-camion" class="{{ $tipoActual === 'gasto' ? '' : 'hidden' }}">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Camión</label>
                    <select name="camion_id"
                            class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        @foreach($camiones as $camion)
                            <option value="{{ $camion->id }}" {{ (string) old('camion_id', $camionSugerido) === (string) $camion->id ? 'selected' : '' }}>
                                {{ $camion->patente }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button type="submit"
                    class="bg-gray-700 hover:bg-gray-800 text-white font-medium px-5 py-2 rounded shadow transition">
                Cargar
            </button>
            <p class="w-full text-xs text-gray-500">
                El adelanto se le descuenta en la próxima liquidación. El gasto se le devuelve y cuenta como gasto del camión.
            </p>
        </form>
    </div>

    {{-- Lo pendiente: se marca lo que entra en este pago. --}}
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">Sin liquidar</h2>
            <span class="font-bold {{ $saldo >= 0 ? 'text-amber-700' : 'text-red-700' }}">
                {{ $saldo < 0 ? '−' : '' }}$ {{ number_format(abs($saldo), 2, ',', '.') }}
            </span>
        </div>

        @if(! $hayPendientes)
            <div class="text-center py-10 text-gray-500 text-sm">No le debés nada: todo está liquidado.</div>
        @else
            <form method="POST" action="{{ route('choferes.liquidacion.store', $chofer) }}" id="form-liquidacion">
                @csrf

                {{-- Para pagar por quincena, semana o lo que sea: marca todo lo que es hasta esa fecha. --}}
                <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-3 text-sm">
                    <label for="hasta" class="text-gray-600">Marcar hasta el</label>
                    <input type="date" id="hasta"
                           class="border border-gray-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <span class="text-gray-400">·</span>
                    @php
                        $primero = collect([$viajes->min('fecha'), $movimientos->min('fecha')])->filter()->min();
                        $quincena = $primero ? ($primero->day <= 15 ? $primero->copy()->setDay(15) : $primero->copy()->endOfMonth()) : null;
                    @endphp
                    @if($quincena)
                        <button type="button" data-hasta="{{ $quincena->toDateString() }}"
                                class="marcar-hasta text-blue-600 hover:text-blue-800 hover:underline">
                            Primera quincena pendiente (al {{ $quincena->format('d/m') }})
                        </button>
                        <span class="text-gray-400">·</span>
                    @endif
                    <button type="button" data-hasta="" class="marcar-hasta text-blue-600 hover:text-blue-800 hover:underline">Todo</button>
                </div>

                @if($viajes->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" class="marcar-grupo rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                               data-grupo="viaje" checked title="Marcar todos los viajes">
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">N° orden</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Cliente</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Total viaje</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Comisión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($viajes as $viaje)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center">
                                            <input type="checkbox" name="viajes[]" value="{{ $viaje->id }}" checked
                                                   data-grupo="viaje" data-fecha="{{ $viaje->fecha->toDateString() }}"
                                                   data-monto="{{ $viaje->comision_monto }}"
                                                   class="item-liquidacion rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                                        </td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $viaje->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ $viaje->nro_orden ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ $viaje->cliente?->nombre ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $viaje->ruta() }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">$ {{ number_format($viaje->total, 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            $ {{ number_format($viaje->comision_monto, 2, ',', '.') }}
                                            @if($viaje->comision_porcentaje !== null)
                                                <span class="text-xs font-normal text-gray-500">({{ \App\Models\Equipo::porcentajeFormateado($viaje->comision_porcentaje) }}%)</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($movimientos->isNotEmpty())
                    <div class="overflow-x-auto {{ $viajes->isNotEmpty() ? 'border-t-4 border-gray-100' : '' }}">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-center w-10">
                                        <input type="checkbox" class="marcar-grupo rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                               data-grupo="movimiento" checked title="Marcar todos los adelantos y gastos">
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Adelantos y gastos</th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Monto</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($movimientos as $movimiento)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-center">
                                            <input type="checkbox" name="movimientos[]" value="{{ $movimiento->id }}" checked
                                                   data-grupo="movimiento" data-fecha="{{ $movimiento->fecha->toDateString() }}"
                                                   data-monto="{{ $movimiento->montoConSigno() }}"
                                                   class="item-liquidacion rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                                        </td>
                                        <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $movimiento->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-700">
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                                         {{ $movimiento->esAdelanto() ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                                                {{ $movimiento->esAdelanto() ? 'Adelanto' : 'Gasto' }}
                                            </span>
                                            {{ $movimiento->concepto }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-semibold whitespace-nowrap {{ $movimiento->esAdelanto() ? 'text-red-700' : 'text-green-700' }}">
                                            {{ $movimiento->esAdelanto() ? '−' : '+' }}$ {{ number_format($movimiento->monto, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            {{-- El formulario de borrar va afuera: no se pueden anidar formularios. --}}
                                            <button type="submit" form="borrar-movimiento-{{ $movimiento->id }}"
                                                    class="text-red-600 hover:text-red-800 text-xs px-2 py-1 rounded border border-red-200 hover:bg-red-50 transition">
                                                Borrar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- La cuenta de lo marcado. --}}
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm mb-4">
                        <div>
                            <dt class="text-xs text-gray-500">Comisiones</dt>
                            <dd class="font-semibold text-gray-900" id="suma-comisiones">$ {{ number_format($comisiones, 2, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Adelantos</dt>
                            <dd class="font-semibold text-red-700" id="suma-adelantos">−$ {{ number_format($adelantos, 2, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Gastos a devolverle</dt>
                            <dd class="font-semibold text-green-700" id="suma-gastos">+$ {{ number_format($gastos, 2, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">A pagarle</dt>
                            <dd class="text-lg font-bold text-gray-900" id="suma-total">$ {{ number_format($saldo, 2, ',', '.') }}</dd>
                        </div>
                    </dl>

                    <div class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha del pago</label>
                            <input type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}"
                                   class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                                          @error('fecha') border-red-400 @enderror">
                            @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notas</label>
                            <input type="text" name="notas" maxlength="500" value="{{ old('notas') }}" placeholder="Cómo se le pagó, lo que quieras recordar…"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button type="submit" id="btn-liquidar"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded shadow transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Registrar pago de <span id="total-boton">$ {{ number_format($saldo, 2, ',', '.') }}</span>
                        </button>
                    </div>
                    <p id="aviso-negativo" class="hidden text-xs text-red-600 mt-2">
                        Los adelantos marcados superan lo que se le debe. Dejá algún adelanto para la próxima liquidación.
                    </p>
                    @error('viajes') <p class="text-red-500 text-xs mt-2">{{ $message }}</p> @enderror
                </div>
            </form>

            @foreach($movimientos as $movimiento)
                <form method="POST" action="{{ route('choferes.movimientos.destroy', $movimiento) }}" id="borrar-movimiento-{{ $movimiento->id }}"
                      onsubmit="return confirm('¿Borrar este {{ $movimiento->esAdelanto() ? 'adelanto' : 'gasto' }}?')" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>

    {{-- Lo que ya se le pagó. --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Liquidaciones hechas</h2>
        </div>

        @if($liquidaciones->isEmpty())
            <div class="text-center py-10 text-gray-500 text-sm">Todavía no le liquidaste nada.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Pagado el</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Viajes</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Comisiones</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Adelantos</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Gastos</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-600">Pagado</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($liquidaciones as $liquidacion)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">{{ $liquidacion->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if($liquidacion->viajes_count > 0)
                                        {{ $liquidacion->viajes_count }} viaje{{ $liquidacion->viajes_count !== 1 ? 's' : '' }}
                                        · del {{ \Carbon\Carbon::parse($liquidacion->viajes_min_fecha)->format('d/m') }}
                                        al {{ \Carbon\Carbon::parse($liquidacion->viajes_max_fecha)->format('d/m/Y') }}
                                    @else
                                        Sin viajes
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 whitespace-nowrap">$ {{ number_format($liquidacion->comisiones, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap {{ $liquidacion->adelantos > 0 ? 'text-red-700' : 'text-gray-400' }}">
                                    {{ $liquidacion->adelantos > 0 ? '−$ ' . number_format($liquidacion->adelantos, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap {{ $liquidacion->gastos > 0 ? 'text-green-700' : 'text-gray-400' }}">
                                    {{ $liquidacion->gastos > 0 ? '+$ ' . number_format($liquidacion->gastos, 2, ',', '.') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">$ {{ number_format($liquidacion->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('liquidaciones.show', $liquidacion) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-xs px-2 py-1 rounded border border-blue-200 hover:bg-blue-50 transition whitespace-nowrap">
                                        Ver detalle
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    // El concepto sugerido y el camión dependen de si es adelanto o gasto.
    const concepto     = document.getElementById('concepto');
    const bloqueCamion = document.getElementById('bloque-camion');
    document.querySelectorAll('.tipo-movimiento').forEach(radio => radio.addEventListener('change', () => {
        const gasto = radio.value === 'gasto' && radio.checked;
        concepto.placeholder = gasto ? 'Peaje, gomería, comida en ruta…' : 'A cuenta de la quincena';
        bloqueCamion?.classList.toggle('hidden', ! gasto);
    }));

    const form = document.getElementById('form-liquidacion');
    if (! form) return;

    const items  = [...form.querySelectorAll('.item-liquidacion')];
    const grupos = [...form.querySelectorAll('.marcar-grupo')];
    const hasta  = document.getElementById('hasta');
    const boton  = document.getElementById('btn-liquidar');
    const aviso  = document.getElementById('aviso-negativo');
    const pesos  = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const el = id => document.getElementById(id);

    function sumar() {
        let comisiones = 0, adelantos = 0, gastos = 0;

        items.filter(i => i.checked).forEach(i => {
            const monto = parseFloat(i.dataset.monto);
            if (i.dataset.grupo === 'viaje') comisiones += monto;
            else if (monto < 0) adelantos -= monto;
            else gastos += monto;
        });

        const total = comisiones - adelantos + gastos;
        el('suma-comisiones').textContent = '$ ' + pesos.format(comisiones);
        el('suma-adelantos').textContent  = '−$ ' + pesos.format(adelantos);
        el('suma-gastos').textContent     = '+$ ' + pesos.format(gastos);
        el('suma-total').textContent      = (total < 0 ? '−' : '') + '$ ' + pesos.format(Math.abs(total));
        el('total-boton').textContent     = el('suma-total').textContent;

        const ninguno = ! items.some(i => i.checked);
        boton.disabled = ninguno || total < 0;
        aviso.classList.toggle('hidden', total >= 0);

        grupos.forEach(g => {
            const delGrupo = items.filter(i => i.dataset.grupo === g.dataset.grupo);
            const marcados = delGrupo.filter(i => i.checked).length;
            g.checked = marcados === delGrupo.length;
            g.indeterminate = marcados > 0 && marcados < delGrupo.length;
        });
    }

    function marcarHasta(fecha) {
        items.forEach(i => i.checked = ! fecha || i.dataset.fecha <= fecha);
        sumar();
    }

    grupos.forEach(g => g.addEventListener('change', () => {
        items.filter(i => i.dataset.grupo === g.dataset.grupo).forEach(i => i.checked = g.checked);
        sumar();
    }));
    items.forEach(i => i.addEventListener('change', sumar));
    hasta.addEventListener('change', () => marcarHasta(hasta.value));
    document.querySelectorAll('.marcar-hasta').forEach(b => b.addEventListener('click', () => {
        hasta.value = b.dataset.hasta;
        marcarHasta(b.dataset.hasta);
    }));

    sumar();
})();
</script>
@endsection
