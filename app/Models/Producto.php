<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'unidad', 'activo', 'created_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * El viaje guarda el producto por nombre, no por id (igual que el
     * destino), así que la relación va por ahí.
     */
    public function viajes()
    {
        return $this->hasMany(Viaje::class, 'producto', 'nombre');
    }

    public function tarifas()
    {
        return $this->hasMany(Tarifa::class, 'producto', 'nombre');
    }

    /** "Toneladas", el texto libre que se haya cargado, o "—". */
    public function unidadEtiqueta(): string
    {
        if (! $this->unidad) {
            return '—';
        }

        return Viaje::$unidades[$this->unidad] ?? $this->unidad;
    }

    /**
     * Los activos para elegir en un formulario, más el que ya tiene cargado
     * lo que se edita aunque esté inactivo.
     */
    public static function paraFormulario(?string $actual = null)
    {
        return static::where('activo', true)
            ->when($actual, fn ($q, $nombre) => $q->orWhere('nombre', $nombre))
            ->orderBy('nombre')
            ->get();
    }
}
