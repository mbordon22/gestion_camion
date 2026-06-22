{{-- Tabla de cuotas del préstamo. Espera $prestamo con relación cuotas cargada. --}}
<div class="bg-white rounded-lg shadow overflow-hidden mt-6">
    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <h2 class="font-semibold text-gray-700">Cuotas</h2>
        <span class="text-sm text-gray-500">
            {{ $prestamo->cuotasPagadas() }}/{{ $prestamo->cantidad_cuotas }} pagadas ·
            saldo $ {{ number_format($prestamo->saldoPendiente(), 2, ',', '.') }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Vencimiento</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Monto</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Estado</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($prestamo->cuotas as $cuota)
                    <tr class="hover:bg-gray-50 {{ $cuota->pagada ? 'bg-green-50/40' : '' }}">
                        <td class="px-4 py-3 text-gray-700">{{ $cuota->numero }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $cuota->fecha_venc->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">$ {{ number_format($cuota->monto, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($cuota->pagada)
                                <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                    Pagada{{ $cuota->fecha_pago ? ' · ' . $cuota->fecha_pago->format('d/m/Y') : '' }}
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">Impaga</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <form method="POST" action="{{ route('cuotas.toggle', $cuota) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="text-xs px-2 py-1 rounded border transition
                                            {{ $cuota->pagada
                                                ? 'text-gray-600 border-gray-300 hover:bg-gray-100'
                                                : 'text-green-700 border-green-300 hover:bg-green-50' }}">
                                    {{ $cuota->pagada ? 'Desmarcar' : 'Marcar pagada' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
