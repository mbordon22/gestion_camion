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

    /**
     * Lo avanzado, que cada cuenta prende si lo necesita. Apagado no se ve
     * (ni en el menú ni al cargar), pero lo que ya estaba cargado no se
     * borra ni cambia, y los reportes lo siguen contando.
     */
    public const FUNCIONES = [
        'orden' => [
            'titulo'      => 'N° de orden o remito',
            'descripcion' => 'Anotar en cada viaje el número de la orden de carga o del remito, y que avise si se repite.',
        ],
        'tarifas' => [
            'titulo'      => 'Tarifas por distancia',
            'descripcion' => 'Una lista de precios por cliente y por km: al cargar el viaje, el precio por tonelada (o por unidad) se completa solo.',
        ],
        'equipos' => [
            'titulo'      => 'Equipos y su alquiler',
            'descripcion' => 'Acoplados, semis o cisternas. Si alguno es alquilado, se descuenta lo que se lleva el dueño y se lleva la cuenta de lo que le debés.',
        ],
        'comisiones' => [
            'titulo'      => 'Choferes a comisión',
            'descripcion' => 'El chofer se lleva un porcentaje o un monto por viaje. Incluye adelantos, gastos que pagó él y la liquidación para mandarle por WhatsApp.',
        ],
        'tarjetas' => [
            'titulo'      => 'Tarjetas de crédito',
            'descripcion' => 'Gastos con tarjeta que se pagan después: con el día de cierre y de vencimiento, te muestra cuánto vas a pagar cada mes.',
        ],
    ];

    protected $fillable = ['nombre', 'notas', 'activa', 'funciones'];

    protected $casts = [
        'activa'    => 'boolean',
        'funciones' => 'array',
    ];

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    public function usa(string $funcion): bool
    {
        return in_array($funcion, $this->funciones ?? [], true);
    }

    /** Sólo las que existen y en el orden de FUNCIONES, venga como venga del formulario. */
    public static function funcionesValidas(?array $elegidas): array
    {
        return array_values(array_intersect(array_keys(self::FUNCIONES), $elegidas ?? []));
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
