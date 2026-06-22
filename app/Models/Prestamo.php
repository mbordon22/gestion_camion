<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestamo extends Model
{
    public $timestamps = false;

    protected $table = 'prestamos';

    protected $fillable = [
        'descripcion', 'categoria', 'monto_total', 'cantidad_cuotas',
        'valor_cuota', 'dia_vencimiento', 'fecha_primera_cuota',
        'medio_pago_id', 'observaciones', 'created_at',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'cantidad_cuotas' => 'integer',
        'valor_cuota' => 'decimal:2',
        'dia_vencimiento' => 'integer',
        'fecha_primera_cuota' => 'date',
        'created_at' => 'datetime',
    ];

    public static array $categorias = [
        'camion'   => 'Camión',
        'personal' => 'Personal',
    ];

    public function cuotas()
    {
        return $this->hasMany(Cuota::class)->orderBy('numero');
    }

    public function medioPago()
    {
        return $this->belongsTo(MedioPago::class);
    }

    public function cuotasPagadas(): int
    {
        return $this->cuotas->where('pagada', true)->count();
    }

    public function saldoPendiente(): float
    {
        return (float) $this->cuotas->where('pagada', false)->sum('monto');
    }

    public function proximaCuota(): ?Cuota
    {
        return $this->cuotas->where('pagada', false)->sortBy('fecha_venc')->first();
    }
}
