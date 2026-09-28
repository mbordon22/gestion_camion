<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Plata que se mueve con el chofer fuera de las comisiones: lo que pidió
 * adelantado (se le descuenta al liquidar) y lo que pagó de su bolsillo por
 * el camión (se le devuelve al liquidar).
 *
 * El adelanto no es un gasto: es parte de su comisión, que ya se cuenta en el
 * viaje. El gasto sí es un costo del camión y entra en la rentabilidad.
 */
class ChoferMovimiento extends Model
{
    public $timestamps = false;

    protected $table = 'chofer_movimientos';

    protected $fillable = [
        'chofer_id', 'camion_id', 'tipo', 'fecha', 'monto', 'concepto', 'liquidacion_id', 'created_at',
    ];

    protected $casts = [
        'chofer_id' => 'integer',
        'camion_id' => 'integer',
        'liquidacion_id' => 'integer',
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public static array $tipos = [
        'adelanto' => 'Adelanto',
        'gasto'    => 'Gasto que pagó él',
    ];

    public function chofer()
    {
        return $this->belongsTo(Chofer::class);
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    public function liquidacion()
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function scopeSinLiquidar($query)
    {
        return $query->whereNull('liquidacion_id');
    }

    public function scopeGastos($query)
    {
        return $query->where('tipo', 'gasto');
    }

    public function scopeAdelantos($query)
    {
        return $query->where('tipo', 'adelanto');
    }

    public function esAdelanto(): bool
    {
        return $this->tipo === 'adelanto';
    }

    /** Cómo pesa en lo que hay que pagarle: el adelanto resta, el gasto suma. */
    public function montoConSigno(): float
    {
        return $this->esAdelanto() ? -(float) $this->monto : (float) $this->monto;
    }
}
