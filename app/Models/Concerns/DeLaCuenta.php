<?php

namespace App\Models\Concerns;

use App\Models\Cuenta;
use App\Support\CuentaActual;
use Illuminate\Database\Eloquent\Builder;

/**
 * Un dato que es de una cuenta: sólo se ve desde ella y se crea en ella.
 *
 * El filtro va en todas las consultas del modelo, las relaciones y el
 * route model binding incluidos: un viaje de otra cuenta da 404, no se
 * puede ni abrir. Para mirar todas las cuentas a la vez (el panel del
 * administrador) está withoutGlobalScope('cuenta').
 */
trait DeLaCuenta
{
    public static function bootDeLaCuenta(): void
    {
        static::addGlobalScope('cuenta', function (Builder $query) {
            if ($cuentaId = CuentaActual::id()) {
                $query->where($query->getModel()->qualifyColumn('cuenta_id'), $cuentaId);
            }
        });

        static::creating(function ($modelo) {
            if (! $modelo->cuenta_id) {
                $modelo->cuenta_id = CuentaActual::id();
            }
        });
    }

    public function cuenta()
    {
        return $this->belongsTo(Cuenta::class);
    }
}
