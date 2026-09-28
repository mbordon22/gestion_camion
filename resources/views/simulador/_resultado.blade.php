{{--
    El resultado de la simulación. Se muestra al entrar y la pantalla lo
    vuelve a pedir (simulador.resultado) cada vez que cambia un dato.
--}}
@php
    $veredicto = $simulacion->veredicto();
    $ganancia = $simulacion->ganancia();
    $margen = $simulacion->margen();
    $pesos = fn ($monto) => '$ ' . number_format(abs($monto), 2, ',', '.');

    $estilos = [
        'conviene'    => ['caja' => 'bg-green-50 border-green-300', 'titulo' => 'text-green-700', 'monto' => 'text-green-800'],
        'justo'       => ['caja' => 'bg-amber-50 border-amber-300', 'titulo' => 'text-amber-700', 'monto' => 'text-amber-800'],
        'no_conviene' => ['caja' => 'bg-orange-50 border-orange-300', 'titulo' => 'text-orange-700', 'monto' => 'text-orange-800'],
        'pierde'      => ['caja' => 'bg-red-50 border-red-300', 'titulo' => 'text-red-700', 'monto' => 'text-red-800'],
        'sin_datos'   => ['caja' => 'bg-gray-50 border-gray-200', 'titulo' => 'text-gray-600', 'monto' => 'text-gray-400'],
    ][$veredicto['nivel']];

    $porUnidad = $simulacion->porCantidad();
    $cobrarMinimo = $simulacion->precioPara(0);
    $cobrarBueno = $simulacion->precioPara(\App\Support\SimulacionViaje::MARGEN_BUENO);
    $sufijo = $porUnidad ? ' por ' . $simulacion->unidadSingular() : ' el viaje';
    // Para cotizar hace falta saber qué cuesta el viaje y, por cantidad, cuánto se lleva.
    $puedeCotizar = $simulacion->kmTotales() > 0 && ($porUnidad ? $simulacion->cantidad > 0 : true);

    // "Cargar como viaje": todo lo simulado va al formulario de viaje.
    $cargarComoViaje = route('viajes.create', array_filter([
        'simulacion'      => 1,
        'camion_id'       => $valores['camion_id'],
        'cliente_id'      => $valores['cliente_id'],
        'chofer_id'       => $valores['chofer_id'],
        'equipo_id'       => $valores['equipo_id'],
        'modo_cobro'      => $valores['modo'],
        'producto'        => $valores['producto'],
        'cantidad'        => $porUnidad ? $valores['cantidad'] : null,
        'unidad'          => $valores['unidad'],
        'precio_unitario' => $porUnidad ? $valores['precio_unitario'] : null,
        'total'           => $porUnidad ? null : $valores['total'],
        'destino'         => $valores['destino'],
        'km_recorridos'   => $valores['km'],
    ], fn ($valor) => $valor !== null && $valor !== ''));
@endphp

<div class="space-y-4">
    {{-- El veredicto --}}
    <div class="rounded-xl border-2 p-5 {{ $estilos['caja'] }}">
        <p class="text-sm font-bold uppercase tracking-wide {{ $estilos['titulo'] }}">{{ $veredicto['texto'] }}</p>
        @if($margen !== null)
            <p class="text-3xl font-bold mt-1 whitespace-nowrap {{ $estilos['monto'] }}">{{ $ganancia < 0 ? '−' : '' }}{{ $pesos($ganancia) }}</p>
            <p class="text-sm text-gray-600 mt-1">
                {{ $ganancia < 0 ? 'de pérdida' : 'de ganancia' }} · margen
                <strong class="{{ $estilos['titulo'] }}">{{ $margen < 0 ? '−' : '' }}{{ \App\Support\Numero::texto(abs($margen)) }} %</strong>
                @if($simulacion->gananciaPorKm() !== null)
                    · {{ $simulacion->gananciaPorKm() < 0 ? '−' : '' }}{{ $pesos($simulacion->gananciaPorKm()) }} por km
                @endif
            </p>
        @else
            <p class="text-sm text-gray-500 mt-1">Con los km y lo que te pagan, acá ves cuánto te queda.</p>
        @endif
    </div>

    {{-- Las cuentas --}}
    <div class="bg-white rounded-xl shadow divide-y divide-gray-100 text-sm">
        <div class="flex items-start justify-between gap-3 px-4 py-3">
            <div>
                <p class="font-semibold text-gray-800">Lo que cobrás</p>
                @if($simulacion->detalleIngreso())
                    <p class="text-xs text-gray-500">{{ $simulacion->detalleIngreso() }}</p>
                @endif
            </div>
            <p class="font-semibold text-gray-900 whitespace-nowrap">{{ $pesos($simulacion->ingreso()) }}</p>
        </div>

        @forelse($simulacion->gastos() as $gasto)
            <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                <div class="min-w-0">
                    <p class="text-gray-700">{{ $gasto['concepto'] }}</p>
                    @if($gasto['detalle'])
                        <p class="text-xs text-gray-500">{{ $gasto['detalle'] }}</p>
                    @endif
                </div>
                <p class="text-red-700 whitespace-nowrap">−{{ $pesos($gasto['monto']) }}</p>
            </div>
        @empty
            <p class="px-4 py-3 text-xs text-gray-500">Todavía no hay gastos cargados.</p>
        @endforelse

        <div class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50">
            <p class="text-gray-600">Total de gastos</p>
            <p class="font-semibold text-gray-800 whitespace-nowrap">−{{ $pesos($simulacion->totalGastos()) }}</p>
        </div>
        <div class="flex items-center justify-between gap-3 px-4 py-3">
            <p class="font-bold text-gray-900">Te queda</p>
            <p class="font-bold whitespace-nowrap {{ $ganancia < 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $ganancia < 0 ? '−' : '' }}{{ $pesos($ganancia) }}</p>
        </div>
    </div>

    {{-- Para cotizar: a cuánto cobrarlo --}}
    @if($puedeCotizar)
        <div class="bg-white rounded-xl shadow p-4 text-sm">
            <h2 class="font-semibold text-gray-800 mb-2">Para cotizar este viaje</h2>
            <dl class="space-y-1.5">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Para no perder, cobrá al menos</dt>
                    <dd class="font-semibold text-gray-900 whitespace-nowrap">{{ $cobrarMinimo !== null ? $pesos($cobrarMinimo) . $sufijo : '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Para ganar un {{ \App\Support\SimulacionViaje::MARGEN_BUENO }} %</dt>
                    <dd class="font-semibold text-green-700 whitespace-nowrap">{{ $cobrarBueno !== null ? $pesos($cobrarBueno) . $sufijo : '—' }}</dd>
                </div>
            </dl>
            @if($cobrarMinimo === null)
                <p class="text-xs text-red-700 mt-2">Entre el equipo y el chofer se llevan todo: con esos porcentajes ningún precio alcanza.</p>
            @endif
        </div>
    @endif

    <a href="{{ $cargarComoViaje }}"
       class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Cargar como viaje
    </a>
    <p class="text-xs text-gray-500 text-center">Es una estimación: no incluye impuestos ni los gastos fijos del mes (seguro, patente, cuotas).</p>
</div>

{{-- En el celular el resultado queda abajo del formulario: el número, siempre a la vista. --}}
@if($margen !== null)
    <a href="#resultado"
       class="sm:hidden fixed bottom-0 inset-x-0 z-20 flex items-center justify-between gap-3 px-4 py-3 border-t-2 shadow-lg {{ $estilos['caja'] }}">
        <span class="text-sm font-bold {{ $estilos['titulo'] }}">{{ $veredicto['texto'] }}</span>
        <span class="text-sm text-gray-700 whitespace-nowrap">
            <strong class="{{ $estilos['monto'] }}">{{ $ganancia < 0 ? '−' : '' }}{{ $pesos($ganancia) }}</strong> · {{ $margen < 0 ? '−' : '' }}{{ \App\Support\Numero::texto(abs($margen)) }} %
        </span>
    </a>
@endif
