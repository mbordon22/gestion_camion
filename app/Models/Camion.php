<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camion extends Model
{
    public $timestamps = false;

    protected $table = 'camiones';

    protected $fillable = [
        'patente', 'marca', 'modelo', 'anio', 'consumo_cada_100km', 'activo', 'observaciones', 'created_at',
    ];

    protected $casts = [
        'anio' => 'integer',
        'consumo_cada_100km' => 'decimal:1',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function viajes()
    {
        return $this->hasMany(Viaje::class);
    }

    public function combustibles()
    {
        return $this->hasMany(Combustible::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function nombre(): string
    {
        $vehiculo = trim(($this->marca ?? '') . ' ' . ($this->modelo ?? ''));
        return $vehiculo === '' ? $this->patente : "{$this->patente} — {$vehiculo}";
    }
}
