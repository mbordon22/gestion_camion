@extends('layouts.app')

@section('title', 'Pagos al dueño del equipo')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('equipos.index') }}" class="text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Pagos al dueño · {{ $equipo->etiqueta() }}</h1>
    </div>
    <p class="text-sm text-gray-500 mb-6">
        {{ $equipo->propietario ?: 'Dueño sin cargar' }} · {{ $equipo->condicion() }}
    </p>

    {{-- Lo que se le debe: se marcan los viajes que se le pagan ahora. --}}
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <h2 class="font-semibold text-gray-700">Le debés</h2>
            <span class="font-bold text-amber-700">$ {{ number_format($pendientes->sum('alquiler_monto'), 2, ',', '.') }}</span>
        </div>

        @if($pendientes->isEmpty())
            <div class="text-center py-10 text-gray-500 text-sm">No le debés nada: todos los viajes con este equipo están pagados.</div>
        @else
            <form method="POST" action="{{ route('equipos.pagos.store', $equipo) }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center">
                                    <input type="checkbox" id="marcar-todos" checked title="Marcar todos"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                                </th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Fecha</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">N° orden</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ruta</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Total viaje</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Para el dueño</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($pendientes as $viaje)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-center">
                                        <input type="checkbox" name="viajes[]" value="{{ $viaje->id }}" checked
                                               data-monto="{{ $viaje->alquiler_monto }}"
                                               class="viaje-a-pagar rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                                    </td>
                                    <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $viaje->fecha->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $viaje->nro_orden ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-600">{{ $viaje->ruta() }}</td>
                                    <td class="px-4 py-2 text-right text-gray-700 whitespace-nowrap">$ {{ number_format($viaje->total, 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">
                                        $ {{ number_format($viaje->alquiler_monto, 2, ',', '.') }}
                                        @if($viaje->alquiler_porcentaje !== null)
                                            <span class="text-xs font-normal text-gray-500">({{ \App\Models\Equipo::porcentajeFormateado($viaje->alquiler_porcentaje) }}%)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50 flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha del pago</label>
                        <input type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}"
                               class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400
                                      @error('fecha') border-red-400 @enderror">
                        @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2 rounded shadow transition">
                        Registrar pago de <span id="total-a-pagar">$ {{ number_format($pendientes->sum('alquiler_monto'), 2, ',', '.') }}</span>
                    </button>
                    @error('viajes') <p class="text-red-500 text-xs w-full">{{ $message }}</p> @enderror
                </div>
            </form>
        @endif
    </div>

    {{-- Lo que ya se le pagó, agrupado por día de pago. --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-700">Pagos hechos</h2>
        </div>

        @if($pagos->isEmpty())
            <div class="text-center py-10 text-gray-500 text-sm">Todavía no registraste pagos a este dueño.</div>
        @else
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Pagado el</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600">Viajes</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Monto</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($pagos as $pago)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-800 font-medium whitespace-nowrap">{{ $pago->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                {{ $pago->viajes }} viaje{{ $pago->viajes !== 1 ? 's' : '' }}
                                · del {{ $pago->desde->format('d/m') }} al {{ $pago->hasta->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">$ {{ number_format($pago->monto, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('equipos.pagos.destroy', [$equipo, $pago->dia]) }}"
                                      onsubmit="return confirm('¿Deshacer este pago? Esos viajes vuelven a figurar como adeudados.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 font-medium text-xs px-2 py-1 rounded border border-red-200 hover:bg-red-50 transition">
                                        Deshacer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
(function () {
    const todos  = document.getElementById('marcar-todos');
    const viajes = [...document.querySelectorAll('.viaje-a-pagar')];
    const total  = document.getElementById('total-a-pagar');
    if (! todos) return;

    const pesos = new Intl.NumberFormat('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function sumar() {
        const marcados = viajes.filter(v => v.checked);
        total.textContent = '$ ' + pesos.format(marcados.reduce((s, v) => s + parseFloat(v.dataset.monto), 0));
        todos.checked = marcados.length === viajes.length;
        todos.indeterminate = marcados.length > 0 && marcados.length < viajes.length;
    }

    todos.addEventListener('change', () => {
        viajes.forEach(v => v.checked = todos.checked);
        sumar();
    });
    viajes.forEach(v => v.addEventListener('change', sumar));
})();
</script>
@endsection
