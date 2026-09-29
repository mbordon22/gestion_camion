<?php

namespace App\Models;

use App\Models\Concerns\DeLaCuenta;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use DeLaCuenta;

    public $timestamps = false;

    protected $fillable = [
        'cliente_id', 'nombre', 'origen', 'km', 'notas', 'activo', 'created_at',
    ];

    protected $casts = [
        'cliente_id' => 'integer',
        'km' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * El viaje guarda el destino por nombre, no por id, así que la relación va
     * por ahí. Cuenta los viajes a ese lugar sin importar el cliente: dos
     * destinos con el mismo nombre comparten el conteo.
     */
    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'destino', 'nombre');
    }

    /** "Churqui" o "Churqui (EFASS)" cuando el destino es de un cliente. */
    public function etiqueta(): string
    {
        return $this->cliente
            ? "{$this->nombre} ({$this->cliente->nombre})"
            : $this->nombre;
    }

    /** "12 km" o "—" si todavía no se cargó la distancia. */
    public function kmFormateado(): string
    {
        return $this->km === null ? '—' : "{$this->km} km";
    }
}
