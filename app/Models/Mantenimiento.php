<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    public $timestamps = false;

    protected $table = 'mantenimiento';

    protected $fillable = [
        'fecha', 'tipo', 'monto', 'km_actuales',
        'proximo_service', 'detalle', 'medio_pago_id', 'fecha_vencimiento', 'created_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'km_actuales' => 'integer',
        'proximo_service' => 'integer',
        'fecha_vencimiento' => 'date',
        'created_at' => 'datetime',
    ];

    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class);
    }

    public static array $tipos = [
        'aceite' => 'Aceite',
        'filtros' => 'Filtros',
        'neumaticos' => 'Neumáticos',
        'frenos' => 'Frenos',
        'repuesto' => 'Repuesto',
        'service' => 'Service',
        'otro' => 'Otro',
    ];
}
