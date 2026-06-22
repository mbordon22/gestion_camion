<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuota extends Model
{
    public $timestamps = false;

    protected $table = 'cuotas';

    protected $fillable = [
        'prestamo_id', 'numero', 'fecha_venc', 'monto', 'pagada', 'fecha_pago', 'created_at',
    ];

    protected $casts = [
        'prestamo_id' => 'integer',
        'numero' => 'integer',
        'fecha_venc' => 'date',
        'monto' => 'decimal:2',
        'pagada' => 'boolean',
        'fecha_pago' => 'date',
        'created_at' => 'datetime',
    ];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class);
    }
}
