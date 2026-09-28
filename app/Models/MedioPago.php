<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MedioPago extends Model
{
    public $timestamps = false;

    protected $table = 'medios_pago';

    protected $fillable = [
        'nombre', 'tipo', 'dia_cierre', 'dia_vencimiento', 'activo', 'created_at',
    ];

    protected $casts = [
        'dia_cierre' => 'integer',
        'dia_vencimiento' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * 'descuento' es el gasto que no sale de tu bolsillo: el cliente te lo da
     * y después te lo descuenta de la factura (el combustible del ingenio, por
     * ejemplo). Cuesta plata igual —por eso es un gasto— pero nunca es un
     * egreso de caja, así que queda fuera de la pantalla de Pagos.
     */
    public static array $tipos = [
        'efectivo'      => 'Efectivo',
        'debito'        => 'Débito',
        'transferencia' => 'Transferencia',
        'credito'       => 'Crédito / Tarjeta',
        'descuento'     => 'Lo descuenta el cliente',
    ];

    public function esCredito(): bool
    {
        return $this->tipo === 'credito';
    }

    public function esDescuento(): bool
    {
        return $this->tipo === 'descuento';
    }

    /**
     * True si es un medio de crédito sin cierre/vencimiento cargados:
     * en ese caso la fecha de pago no se puede deducir y debe indicarse a mano.
     */
    public function requiereFechaPagoManual(): bool
    {
        return $this->esCredito() && (! $this->dia_cierre || ! $this->dia_vencimiento);
    }

    /**
     * True si puede autosugerir la fecha de pago (crédito con cierre y vencimiento).
     */
    public function puedeSugerirFecha(): bool
    {
        return $this->esCredito() && $this->dia_cierre && $this->dia_vencimiento;
    }

    public function combustibles()
    {
        return $this->hasMany(Combustible::class);
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class);
    }

    /**
     * Calcula la fecha en que se va a cobrar un gasto pagado con este medio.
     *
     * - Si no es crédito: se cobra el mismo día del gasto.
     * - Si es crédito: el gasto entra en el resumen que cierra el día `dia_cierre`.
     *   Si el gasto es posterior al cierre, entra en el resumen del mes siguiente.
     *   El vencimiento cuelga de ese cierre: si dia_vencimiento <= dia_cierre vence
     *   el mes posterior al cierre; si es mayor, el mismo mes del cierre.
     *   El día se recorta a fin de mes (ej. día 31 en febrero -> 28/29).
     */
    public function fechaCobro(Carbon $fechaGasto): Carbon
    {
        if (! $this->esCredito() || ! $this->dia_cierre || ! $this->dia_vencimiento) {
            return $fechaGasto->copy()->startOfDay();
        }

        $cierre = $this->dia_cierre;
        $venc   = $this->dia_vencimiento;

        // 1) Mes del cierre que captura el gasto
        $mesCierre = $fechaGasto->copy();
        if ($fechaGasto->day > $cierre) {
            $mesCierre->addMonthNoOverflow();
        }

        // 2) El vencimiento cuelga del cierre
        $mesVenc = $mesCierre->copy();
        if ($venc <= $cierre) {
            $mesVenc->addMonthNoOverflow();
        }

        // 3) Aplicar el día de vencimiento recortado a fin de mes
        $dia = min($venc, $mesVenc->daysInMonth);

        return $mesVenc->day($dia)->startOfDay();
    }
}
