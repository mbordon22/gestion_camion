<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'camion_id', 'modo_cobro', 'fecha', 'fecha_carga',
        'cantidad', 'unidad', 'precio_unitario', 'total', 'cobrado',
        'origen', 'destino', 'km_recorridos', 'observaciones', 'created_at',
    ];

    protected $casts = [
        'camion_id' => 'integer',
        'fecha' => 'datetime',
        'fecha_carga' => 'date',
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'cobrado' => 'boolean',
        'km_recorridos' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Cómo se cobra el viaje:
     *  - fijo     -> el usuario escribe el total
     *  - cantidad -> cantidad x precio_unitario
     */
    public static array $modosCobro = [
        'fijo'     => 'Monto fijo por el viaje',
        'cantidad' => 'Por cantidad',
    ];

    /** Unidades sugeridas. El campo acepta texto libre para cualquier otra. */
    public static array $unidades = [
        'bolsas'    => 'Bolsas',
        'toneladas' => 'Toneladas',
        'pallets'   => 'Pallets',
        'cabezas'   => 'Cabezas',
    ];

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    public function esMontoFijo(): bool
    {
        return $this->modo_cobro === 'fijo';
    }

    /**
     * Resumen de la carga para mostrar en una sola columna de los listados:
     * "800 bolsas", "28,5 toneladas" o "Monto fijo".
     */
    public function resumenCarga(): string
    {
        if ($this->esMontoFijo() || ! $this->cantidad) {
            return 'Monto fijo';
        }

        return $this->cantidadFormateada() . ' ' . $this->unidadEtiqueta();
    }

    /** La cantidad sin decimales si es entera (800), con coma si no (28,5). */
    public function cantidadFormateada(): string
    {
        $cantidad = (float) $this->cantidad;

        if (fmod($cantidad, 1) === 0.0) {
            return number_format($cantidad, 0, ',', '.');
        }

        // 28,50 -> "28,5"; 28,05 se queda como está.
        return rtrim(number_format($cantidad, 2, ',', '.'), '0');
    }

    /** La etiqueta de la unidad, en minúscula, o el texto libre que se haya cargado. */
    public function unidadEtiqueta(): string
    {
        if (! $this->unidad) {
            return '';
        }

        return isset(self::$unidades[$this->unidad])
            ? mb_strtolower(self::$unidades[$this->unidad])
            : $this->unidad;
    }

    /** Origen y destino en una sola línea: "Tucumán → Salta". */
    public function ruta(): string
    {
        $origen  = trim((string) $this->origen);
        $destino = trim((string) $this->destino);

        if ($origen !== '' && $destino !== '') {
            return "{$origen} → {$destino}";
        }

        return $origen !== '' ? $origen : ($destino !== '' ? $destino : '—');
    }
}
