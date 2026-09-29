<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Concerns\DeLaCuenta;
use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    use DeLaCuenta;

    public $timestamps = false;

    protected $fillable = [
        'cliente_id', 'producto', 'km_desde', 'km_hasta',
        'importe', 'unidad', 'vigente_desde', 'notas', 'created_at',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'km_desde' => 'integer',
        'km_hasta' => 'integer',
        'importe' => 'decimal:2',
        'vigente_desde' => 'date',
        'created_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * La tarifa que corresponde a un viaje, o null si no hay ninguna.
     *
     * Gana la del producto puntual sobre la general, y entre varias vigencias
     * la más nueva que ya haya empezado a la fecha del viaje. Un viaje viejo
     * sigue resolviendo con la tarifa que regía ese día.
     */
    public static function paraViaje(?int $clienteId, ?string $producto, ?int $km, $fecha): ?self
    {
        if (! $clienteId || $km === null || ! $fecha) {
            return null;
        }

        // Del formulario viene "2026-09-20T18:47": sin recortar la hora la
        // comparación queda a merced de cómo convierta la base.
        $dia = Carbon::parse($fecha)->toDateString();

        return static::where('cliente_id', $clienteId)
            ->where('km_desde', '<=', $km)
            ->where('km_hasta', '>=', $km)
            ->whereDate('vigente_desde', '<=', $dia)
            ->where(fn ($q) => $q->whereNull('producto')->orWhere('producto', $producto))
            ->orderByRaw('producto IS NULL')
            ->orderByDesc('vigente_desde')
            ->first();
    }

    /** "0 a 8 km". */
    public function rango(): string
    {
        return "{$this->km_desde} a {$this->km_hasta} km";
    }

    /** "$ 8.500,00 por tonelada". */
    public function precioPorUnidad(): string
    {
        return '$ ' . number_format($this->importe, 2, ',', '.') . ' por ' . $this->unidadSingular();
    }

    /** Una línea para el formulario de viaje: rango, precio y desde cuándo. */
    public function detalle(): string
    {
        return $this->rango() . ' · ' . $this->precioPorUnidad()
            . ' · desde el ' . $this->vigente_desde->format('d/m/Y');
    }

    /** "toneladas" -> "tonelada", para que el precio se lea "por tonelada". */
    public function unidadSingular(): string
    {
        $unidad = mb_strtolower((string) $this->unidad);

        return str_ends_with($unidad, 's') ? mb_substr($unidad, 0, -1) : $unidad;
    }

    /** Si es la que se aplica hoy para ese cliente, producto y rango. */
    public function estaVigente(): bool
    {
        // Todavía no empezó.
        if ($this->vigente_desde->isFuture()) {
            return false;
        }

        // Ya la tapó una más nueva que también empezó.
        return ! static::where('cliente_id', $this->cliente_id)
            ->where('producto', $this->producto)
            ->where('km_desde', $this->km_desde)
            ->where('km_hasta', $this->km_hasta)
            ->where('vigente_desde', '>', $this->vigente_desde)
            ->whereDate('vigente_desde', '<=', today())
            ->exists();
    }
}
