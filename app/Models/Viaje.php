<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'fecha', 'fecha_carga', 'nro_ingreso', 'tipo_ingreso', 'motivo',
        'bolsas', 'precio_bolsa', 'total', 'facturado', 'kg_netos', 'destino',
        'observaciones', 'created_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_carga' => 'date',
        'bolsas' => 'integer',
        'precio_bolsa' => 'decimal:2',
        'total' => 'decimal:2',
        'facturado' => 'boolean',
        'kg_netos' => 'decimal:2',
        'created_at' => 'datetime',
    ];
}
