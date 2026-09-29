<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un cliente del sistema: un transportista con sus camiones, viajes y
 * gastos. Sus usuarios sólo ven lo de su cuenta (Concerns\DeLaCuenta).
 */
class Cuenta extends Model
{
    protected $table = 'cuentas';

    protected $fillable = ['nombre', 'notas', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Lo que una cuenta nueva necesita para arrancar: los medios de pago
     * más comunes, y el camión si ya se sabe la patente.
     */
    public function prepararDatosIniciales(?string $patente = null): void
    {
        foreach (['Efectivo' => 'efectivo', 'Transferencia' => 'transferencia'] as $nombre => $tipo) {
            $this->crearSiFalta(MedioPago::class, ['nombre' => $nombre], ['tipo' => $tipo, 'activo' => true]);
        }

        if ($patente) {
            $this->crearSiFalta(Camion::class, ['patente' => mb_strtoupper(trim($patente))], ['activo' => true]);
        }
    }

    /**
     * Crea el dato en ESTA cuenta, no en la de quien está logueado (el
     * administrador). cuenta_id no se asigna en masa a propósito, así que
     * va aparte.
     */
    private function crearSiFalta(string $modelo, array $busqueda, array $resto): void
    {
        $existe = $modelo::withoutGlobalScope('cuenta')->where('cuenta_id', $this->id)->where($busqueda)->exists();

        if (! $existe) {
            $nuevo = new $modelo($busqueda + $resto);
            $nuevo->cuenta_id = $this->id;
            $nuevo->save();
        }
    }
}
