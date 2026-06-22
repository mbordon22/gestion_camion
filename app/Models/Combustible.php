<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Combustible extends Model
{
    public $timestamps = false;

    protected $table = 'combustible';

    protected $fillable = [
        'fecha', 'litros', 'precio_litro', 'total',
        'km_odometro', 'lugar', 'medio_pago_id', 'fecha_vencimiento', 'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'litros' => 'decimal:2',
        'precio_litro' => 'decimal:2',
        'total' => 'decimal:2',
        'km_odometro' => 'integer',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
    ];

    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class);
    }
}
