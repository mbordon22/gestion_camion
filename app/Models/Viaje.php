<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'camion_id', 'cliente_id', 'chofer_id', 'modo_cobro', 'fecha', 'fecha_carga',
        'nro_orden', 'producto', 'cantidad', 'unidad', 'precio_unitario', 'total', 'cobrado',
        'origen', 'destino', 'km_recorridos', 'observaciones', 'created_at',
    ];

    protected $casts = [
        'camion_id' => 'integer',
        'cliente_id' => 'integer',
        'chofer_id' => 'integer',
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
        'toneladas' => 'Toneladas',
        'kilogramos'   => 'Kilogramos',
        'bolsas'    => 'Bolsas',
        'pallets'   => 'Pallets',
        'cabezas'   => 'Cabezas',
    ];

    /**
     * Sugerencias de producto para el formulario: lo que ya se cargó alguna
     * vez, más los habituales, para no tipear "Vinaza" en cada viaje.
     */
    public static function productosSugeridos(): array
    {
        return static::query()->whereNotNull('producto')->distinct()->pluck('producto')
            ->merge(['Vinaza', 'Azúcar'])
            ->unique()
            ->sort(SORT_LOCALE_STRING)
            ->values()
            ->all();
    }

    public function camion()
    {
        return $this->belongsTo(Camion::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function chofer()
    {
        return $this->belongsTo(Chofer::class);
    }

    public function esMontoFijo(): bool
    {
        return $this->modo_cobro === 'fijo';
    }

    /**
     * Resumen de la carga para mostrar en una sola columna de los listados:
     * "Vinaza · 27,7 toneladas", "800 bolsas" o "Monto fijo".
     *
     * La cantidad ya no depende del modo de cobro: un flete de precio cerrado
     * igual llevó una carga y el peso del ticket tiene que quedar registrado.
     */
    public function resumenCarga(): string
    {
        $carga = $this->cantidad
            ? $this->cantidadFormateada() . ' ' . $this->unidadEtiqueta()
            : null;

        return collect([$this->producto, $carga])->filter()->implode(' · ') ?: 'Monto fijo';
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
