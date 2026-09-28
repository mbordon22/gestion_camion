<?php

namespace App\Models;

use App\Support\Numero;
use Illuminate\Database\Eloquent\Model;

class Viaje extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'camion_id', 'cliente_id', 'chofer_id', 'equipo_id', 'modo_cobro', 'fecha', 'fecha_carga',
        'nro_orden', 'producto', 'cantidad', 'unidad', 'precio_unitario', 'total', 'cobrado',
        'alquiler_porcentaje', 'alquiler_monto', 'alquiler_pagado_el',
        'comision_porcentaje', 'comision_monto', 'liquidacion_id',
        'origen', 'destino', 'km_recorridos', 'observaciones', 'created_at',
    ];

    protected $casts = [
        'camion_id' => 'integer',
        'cliente_id' => 'integer',
        'chofer_id' => 'integer',
        'equipo_id' => 'integer',
        'fecha' => 'datetime',
        'fecha_carga' => 'date',
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'cobrado' => 'boolean',
        'alquiler_porcentaje' => 'decimal:2',
        'alquiler_monto' => 'decimal:2',
        'alquiler_pagado_el' => 'date',
        'comision_porcentaje' => 'decimal:2',
        'comision_monto' => 'decimal:2',
        'liquidacion_id' => 'integer',
        'km_recorridos' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Cómo se cobra el viaje:
     *  - fijo     -> el usuario escribe el total
     *  - cantidad -> cantidad x precio_unitario
     */
    public static array $modosCobro = [
        'fijo'     => 'Precio cerrado',
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

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function liquidacion()
    {
        return $this->belongsTo(Liquidacion::class);
    }

    /** Lo que le toca al dueño del equipo alquilado y todavía no se le pagó. */
    public function scopeAlquilerSinPagar($query)
    {
        return $query->where('alquiler_monto', '>', 0)->whereNull('alquiler_pagado_el');
    }

    /** La comisión del chofer que todavía no entró en ninguna liquidación. */
    public function scopeComisionSinLiquidar($query)
    {
        return $query->where('comision_monto', '>', 0)->whereNull('liquidacion_id');
    }

    /** Lo que queda para el camión después de pagarle al dueño del equipo y al chofer. */
    public function netoCamion(): float
    {
        return (float) $this->total - (float) $this->alquiler_monto - (float) $this->comision_monto;
    }

    public function esMontoFijo(): bool
    {
        return $this->modo_cobro === 'fijo';
    }

    /**
     * Resumen de la carga para mostrar en una sola columna de los listados:
     * "Vinaza · 27,7 toneladas", "800 bolsas" o "Precio cerrado".
     *
     * La cantidad ya no depende del modo de cobro: un flete de precio cerrado
     * igual llevó una carga y el peso del ticket tiene que quedar registrado.
     */
    public function resumenCarga(): string
    {
        $carga = $this->cantidad
            ? $this->cantidadFormateada() . ' ' . $this->unidadEtiqueta()
            : null;

        return collect([$this->producto, $carga])->filter()->implode(' · ') ?: 'Precio cerrado';
    }

    /**
     * Qué viaje se está por borrar, para el modal de confirmación:
     * "26/09/2026 · Ingenio la Corona · $ 256.185,02. No se puede deshacer."
     */
    public function resumenParaConfirmar(): string
    {
        return collect([
            $this->fecha->format('d/m/Y'),
            $this->cliente?->nombre,
            '$ ' . number_format($this->total, 2, ',', '.'),
        ])->filter()->implode(' · ') . '. No se puede deshacer.';
    }

    /**
     * La carga en pocas letras, para el listado del celular: "28,17 t",
     * "800 bolsas". Sin cantidad no hay nada que mostrar.
     */
    public function cargaCorta(): ?string
    {
        if (! $this->cantidad) {
            return null;
        }

        $abreviada = ['toneladas' => 't', 'kilogramos' => 'kg'][$this->unidad] ?? $this->unidadEtiqueta();

        return trim($this->cantidadFormateada() . ' ' . $abreviada);
    }

    /** La cantidad sin decimales si es entera (800), con coma si no (28,5). */
    public function cantidadFormateada(): string
    {
        return self::valorCampo($this->cantidad);
    }

    /**
     * Un número en criollo, sin decimales si es entero: "235.450", "27,7"
     * (28,50 -> "28,5"). Sirve también para los campos del formulario,
     * porque el controlador lo sabe volver a leer.
     */
    public static function valorCampo($numero): string
    {
        return Numero::texto($numero);
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
