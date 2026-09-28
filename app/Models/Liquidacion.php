<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un pago al chofer: los viajes y los movimientos que entraron, y los montos
 * de ese día (comisiones − adelantos + gastos = total).
 */
class Liquidacion extends Model
{
    public $timestamps = false;

    protected $table = 'liquidaciones';

    protected $fillable = [
        'chofer_id', 'fecha', 'comisiones', 'adelantos', 'gastos', 'total', 'notas', 'created_at',
    ];

    protected $casts = [
        'chofer_id' => 'integer',
        'fecha' => 'date',
        'comisiones' => 'decimal:2',
        'adelantos' => 'decimal:2',
        'gastos' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function chofer()
    {
        return $this->belongsTo(Chofer::class);
    }

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    public function movimientos()
    {
        return $this->hasMany(ChoferMovimiento::class);
    }
}
