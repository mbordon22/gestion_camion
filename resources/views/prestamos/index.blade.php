@extends('layouts.app')

@section('title', 'Préstamos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Préstamos</h1>
    <a href="{{ route('prestamos.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded shadow transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo préstamo
    </a>
</div>

@if($prestamos->isEmpty())
    <div class="bg-white rounded-lg shadow text-center py-12 text-gray-500">
        <p>No hay préstamos cargados.</p>
        <p class="text-sm mt-1">Cargá un préstamo para proyectar sus cuotas y verlas en Pagos.</p>
    </div>
@else
    @php $totalSaldo = $prestamos->sum(fn($p) => $p->saldoPendiente()); @endphp
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-5">
        <p class="text-xs text-purple-600 font-medium uppercase tracking-wide">Saldo total pendiente (todos los préstamos)</p>
        <p class="text-2xl font-bold text-purple-800 mt-1">$ {{ number_format($totalSaldo, 2, ',', '.') }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4">
        @foreach($prestamos as $prestamo)
            @php
                $pagadas = $prestamo->cuotasPagadas();
                $total   = $prestamo->cantidad_cuotas;
                $pct     = $total > 0 ? round($pagadas / $total * 100) : 0;
                $proxima = $prestamo->proximaCuota();
            @endphp
            <div class="bg-white rounded-lg shadow p-5">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-semibold text-gray-800">{{ $prestamo->descripcion }}</h2>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $prestamo->categoria === 'camion' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ \App\Models\Prestamo::$categorias[$prestamo->categoria] ?? $prestamo->categoria }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $total }} cuotas de $ {{ number_format($prestamo->valor_cuota, 2, ',', '.') }}
                            · total $ {{ number_format($prestamo->monto_total, 2, ',', '.') }}
                            @if($prestamo->medioPago) · {{ $prestamo->medioPago->nombre }} @endif
                        </p>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('prestamos.edit', $prestamo) }}"
                           class="text-blue-600 hover:text-blue-800 font-medium text-xs px-3 py-1.5 rounded border border-blue-200 hover:bg-blue-50 transition">
                            Ver cuotas / Editar
                        </a>
                        <form method="POST" action="{{ route('prestamos.destroy', $prestamo) }}"
                              onsubmit="return confirm('¿Eliminar este préstamo y todas sus cuotas?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-red-600 hover:text-red-800 font-medium text-xs px-3 py-1.5 rounded border border-red-200 hover:bg-red-50 transition">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Barra de progreso --}}
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ $pagadas }}/{{ $total }} cuotas pagadas</span>
                        <span>Saldo: $ {{ number_format($prestamo->saldoPendiente(), 2, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                    @if($proxima)
                        <p class="text-xs text-gray-500 mt-2">
                            Próxima cuota: #{{ $proxima->numero }} el {{ $proxima->fecha_venc->format('d/m/Y') }}
                            · $ {{ number_format($proxima->monto, 2, ',', '.') }}
                        </p>
                    @else
                        <p class="text-xs text-green-600 mt-2 font-medium">✓ Préstamo totalmente pagado</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
