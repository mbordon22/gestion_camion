<?php

namespace App\Models;

use App\Models\Concerns\DeLaCuenta;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use DeLaCuenta;

    public $timestamps = false;

    protected $fillable = [
        'nombre', 'tipo', 'patente', 'camion_id', 'alquilado', 'propietario',
        'modalidad', 'valor', 'notas', 'activo', 'created_at',
    ];

    protected $casts = [
        'camion_id' => 'integer',
        'alquilado' => 'boolean',
        'valor' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Cómo se lleva su parte el que alquila el equipo. El alquiler fijo por
     * mes o por zafra no depende de los viajes y todavía no está: cuando haga
     * falta va aparte, no como una modalidad más de ésta.
     */
    public static array $modalidades = [
        'porcentaje' => 'Porcentaje de cada viaje',
        'fijo_viaje' => 'Monto fijo por viaje',
    ];

    /** Sugerencias para el tipo. El campo acepta cualquier otro. */
    public static array $tipos = ['Cisterna', 'Equipo cañero', 'Acoplado', 'Semirremolque', 'Batea'];

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    public function esPorcentaje(): bool
    {
        return $this->modalidad === 'porcentaje';
    }

    /** "Cisterna (TWH728)". */
    public function etiqueta(): string
    {
        return $this->patente ? "{$this->nombre} ({$this->patente})" : $this->nombre;
    }

    /** "Propio", "25% de cada viaje" o "$ 20.000 por viaje". */
    public function condicion(): string
    {
        if (! $this->alquilado) {
            return 'Propio';
        }

        return $this->esPorcentaje()
            ? self::porcentajeFormateado($this->valor) . '% de cada viaje'
            : '$ ' . number_format((float) $this->valor, 0, ',', '.') . ' por viaje';
    }

    /** 25.00 -> "25"; 12.50 -> "12,5". */
    public static function porcentajeFormateado($porcentaje): string
    {
        return rtrim(rtrim(number_format((float) $porcentaje, 2, ',', ''), '0'), ',');
    }

    /**
     * Lo que se lleva el dueño de un viaje con este total.
     *
     * Si el viaje ya tenía este mismo equipo, se respeta lo que se grabó al
     * cargarlo: editar un viaje viejo no le aplica el acuerdo de hoy. Sólo el
     * monto se recalcula con el total nuevo, porque el total puede corregirse.
     */
    public function alquilerDe(float $total, ?Viaje $anterior = null): array
    {
        if (! $this->alquilado) {
            return ['alquiler_porcentaje' => null, 'alquiler_monto' => null];
        }

        $mismoEquipo = $anterior && $anterior->equipo_id === $this->id;

        if ($this->esPorcentaje()) {
            $porcentaje = $mismoEquipo && $anterior->alquiler_porcentaje !== null
                ? (float) $anterior->alquiler_porcentaje
                : (float) $this->valor;

            return [
                'alquiler_porcentaje' => $porcentaje,
                'alquiler_monto'      => round($total * $porcentaje / 100, 2),
            ];
        }

        $monto = $mismoEquipo && $anterior->alquiler_monto !== null
            ? (float) $anterior->alquiler_monto
            : (float) $this->valor;

        return ['alquiler_porcentaje' => null, 'alquiler_monto' => $monto];
    }
}
